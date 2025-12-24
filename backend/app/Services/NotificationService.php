<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service principal pour la gestion des notifications
 *
 * Ce service gère la création, l'envoi et le suivi des notifications
 * par email aux parents d'élèves.
 */
class NotificationService
{
    /**
     * Envoyer un rappel de paiement des frais de scolarité
     *
     * @param string $recipientEmail Email du parent
     * @param string $recipientName Nom du parent
     * @param array $data Données pour le template (montant, échéance, nom de l'élève, etc.)
     * @param \DateTimeInterface|null $scheduledAt Date d'envoi programmée (null = immédiat)
     * @return Notification
     */
    public function sendPaymentReminder(
        string $recipientEmail,
        string $recipientName,
        array $data = [],
        ?\DateTimeInterface $scheduledAt = null
    ): Notification {
        $template = NotificationTemplate::active()
            ->ofType(Notification::TYPE_PAYMENT_REMINDER)
            ->first();

        if (!$template) {
            throw new \RuntimeException('Template de rappel de paiement non trouvé ou inactif');
        }

        $variables = array_merge([
            'recipient_name' => $recipientName,
            'student_name' => $data['student_name'] ?? '',
            'amount' => $data['amount'] ?? '',
            'due_date' => $data['due_date'] ?? '',
            'tranche' => $data['tranche'] ?? '',
        ], $data);

        return $this->createNotification(
            template: $template,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            variables: $variables,
            scheduledAt: $scheduledAt
        );
    }

    /**
     * Envoyer une notification urgente concernant un élève
     *
     * @param string $recipientEmail Email du parent
     * @param string $recipientName Nom du parent
     * @param array $data Données pour le template (nom de l'élève, type d'urgence, message, etc.)
     * @param \DateTimeInterface|null $scheduledAt Date d'envoi programmée (null = immédiat)
     * @return Notification
     */
    public function sendUrgentNotification(
        string $recipientEmail,
        string $recipientName,
        array $data = [],
        ?\DateTimeInterface $scheduledAt = null
    ): Notification {
        $template = NotificationTemplate::active()
            ->ofType(Notification::TYPE_URGENT_INFO)
            ->first();

        if (!$template) {
            throw new \RuntimeException('Template de notification urgente non trouvé ou inactif');
        }

        $variables = array_merge([
            'recipient_name' => $recipientName,
            'student_name' => $data['student_name'] ?? '',
            'urgency_type' => $data['urgency_type'] ?? '',
            'message' => $data['message'] ?? '',
        ], $data);

        return $this->createNotification(
            template: $template,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            variables: $variables,
            scheduledAt: $scheduledAt
        );
    }

    /**
     * Envoyer une notification générale de l'école
     *
     * @param string $recipientEmail Email du parent
     * @param string $recipientName Nom du parent
     * @param array $data Données pour le template
     * @param \DateTimeInterface|null $scheduledAt Date d'envoi programmée (null = immédiat)
     * @return Notification
     */
    public function sendGeneralNotification(
        string $recipientEmail,
        string $recipientName,
        array $data = [],
        ?\DateTimeInterface $scheduledAt = null
    ): Notification {
        $template = NotificationTemplate::active()
            ->ofType(Notification::TYPE_GENERAL)
            ->first();

        if (!$template) {
            throw new \RuntimeException('Template de notification générale non trouvé ou inactif');
        }

        $variables = array_merge([
            'recipient_name' => $recipientName,
        ], $data);

        return $this->createNotification(
            template: $template,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            variables: $variables,
            scheduledAt: $scheduledAt
        );
    }

    /**
     * Créer une notification à partir d'un template
     *
     * @param NotificationTemplate $template Template à utiliser
     * @param string $recipientEmail Email du destinataire
     * @param string $recipientName Nom du destinataire
     * @param array $variables Variables à remplacer dans le template
     * @param \DateTimeInterface|null $scheduledAt Date d'envoi programmée
     * @return Notification
     */
    public function createNotification(
        NotificationTemplate $template,
        string $recipientEmail,
        string $recipientName,
        array $variables = [],
        ?\DateTimeInterface $scheduledAt = null
    ): Notification {
        // Remplacer les variables dans le sujet et le corps
        $subject = $this->replaceVariables($template->subject, $variables);
        $body = $this->replaceVariables($template->body, $variables);

        $status = $scheduledAt && $scheduledAt > now()
            ? Notification::STATUS_SCHEDULED
            : Notification::STATUS_DRAFT;

        $notification = Notification::create([
            'template_id' => $template->id,
            'type' => $template->type,
            'status' => $status,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'body' => $body,
            'variables' => $variables,
            'scheduled_at' => $scheduledAt,
        ]);

        Log::info('Notification créée', [
            'notification_id' => $notification->id,
            'type' => $notification->type,
            'recipient' => $recipientEmail,
            'status' => $notification->status,
        ]);

        // Si la notification est prête à être envoyée, la dispatcher immédiatement
        if ($status === Notification::STATUS_DRAFT) {
            $this->dispatchNotification($notification);
        }

        return $notification;
    }

    /**
     * Dispatcher une notification pour envoi (via queue)
     *
     * @param Notification $notification
     * @return void
     */
    public function dispatchNotification(Notification $notification): void
    {
        if ($notification->status !== Notification::STATUS_DRAFT) {
            return;
        }

        $notification->update(['status' => Notification::STATUS_SCHEDULED]);

        \App\Jobs\SendNotificationJob::dispatch($notification)
            ->onQueue('notifications');

        Log::info('Notification dispatchée pour envoi', [
            'notification_id' => $notification->id,
        ]);
    }

    /**
     * Remplacer les variables dans un texte
     *
     * @param string $text Texte contenant des variables {{variable_name}}
     * @param array $variables Tableau associatif de variables
     * @return string Texte avec variables remplacées
     */
    protected function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }

        // Nettoyer les variables non remplacées
        $text = preg_replace('/\{\{[^}]+\}\}/', '', $text);

        return $text;
    }

    /**
     * Logger un événement pour une notification
     *
     * @param Notification $notification
     * @param string $event Type d'événement (sent, opened, clicked, failed, bounced)
     * @param array $details Détails supplémentaires
     * @param string|null $ipAddress Adresse IP (pour opened/clicked)
     * @param string|null $userAgent User agent (pour opened/clicked)
     * @return NotificationLog
     */
    public function logEvent(
        Notification $notification,
        string $event,
        array $details = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): NotificationLog {
        return NotificationLog::create([
            'notification_id' => $notification->id,
            'event' => $event,
            'details' => !empty($details) ? json_encode($details) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Obtenir les statistiques d'une notification
     *
     * @param Notification $notification
     * @return array
     */
    public function getNotificationStats(Notification $notification): array
    {
        $logs = $notification->logs;

        return [
            'sent' => $logs->where('event', NotificationLog::EVENT_SENT)->count(),
            'opened' => $logs->where('event', NotificationLog::EVENT_OPENED)->count(),
            'clicked' => $logs->where('event', NotificationLog::EVENT_CLICKED)->count(),
            'failed' => $logs->where('event', NotificationLog::EVENT_FAILED)->count(),
            'bounced' => $logs->where('event', NotificationLog::EVENT_BOUNCED)->count(),
            'last_opened_at' => $logs->where('event', NotificationLog::EVENT_OPENED)->max('occurred_at'),
            'last_clicked_at' => $logs->where('event', NotificationLog::EVENT_CLICKED)->max('occurred_at'),
        ];
    }
}

