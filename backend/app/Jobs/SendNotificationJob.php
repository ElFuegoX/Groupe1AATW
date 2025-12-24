<?php

namespace App\Jobs;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job pour l'envoi asynchrone des notifications par email
 *
 * Ce job gère l'envoi des notifications avec retry logic et logging complet
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives maximum
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Délai avant la prochaine tentative (en secondes)
     *
     * @var int
     */
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Timeout pour le job (en secondes)
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Créer une nouvelle instance du job
     *
     * @param Notification $notification
     */
    public function __construct(
        public Notification $notification
    ) {
        // Spécifier la queue pour ce job
        $this->onQueue('notifications');
    }

    /**
     * Exécuter le job
     *
     * @param NotificationService $notificationService
     * @return void
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            // Vérifier que la notification est toujours en statut scheduled
            if ($this->notification->status !== Notification::STATUS_SCHEDULED) {
                Log::warning('Notification non en statut scheduled, job annulé', [
                    'notification_id' => $this->notification->id,
                    'current_status' => $this->notification->status,
                ]);
                return;
            }

            // Vérifier que la notification est prête à être envoyée
            if ($this->notification->scheduled_at && $this->notification->scheduled_at > now()) {
                Log::info('Notification programmée pour plus tard, job reporté', [
                    'notification_id' => $this->notification->id,
                    'scheduled_at' => $this->notification->scheduled_at,
                ]);
                // Re-dispatch le job pour plus tard
                self::dispatch($this->notification)
                    ->delay($this->notification->scheduled_at->diffInSeconds(now()));
                return;
            }

            Log::info('Début de l\'envoi de la notification', [
                'notification_id' => $this->notification->id,
                'recipient' => $this->notification->recipient_email,
            ]);

            // Envoyer l'email
            Mail::to($this->notification->recipient_email)
                ->send(new NotificationMail($this->notification));

            // Marquer comme envoyée et logger
            $this->notification->markAsSent();
            $notificationService->logEvent(
                $this->notification,
                NotificationLog::EVENT_SENT,
                ['sent_at' => now()->toIso8601String()]
            );

            Log::info('Notification envoyée avec succès', [
                'notification_id' => $this->notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Si c'est la dernière tentative, marquer comme échouée
            if ($this->attempts() >= $this->tries) {
                $this->notification->markAsFailed($e->getMessage());
                $notificationService->logEvent(
                    $this->notification,
                    NotificationLog::EVENT_FAILED,
                    [
                        'error' => $e->getMessage(),
                        'attempts' => $this->attempts(),
                    ]
                );
            } else {
                // Relancer l'exception pour que Laravel retry le job
                throw $e;
            }
        }
    }

    /**
     * Gérer l'échec du job
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job d\'envoi de notification définitivement échoué', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->notification->markAsFailed($exception->getMessage());

        $notificationService = app(NotificationService::class);
        $notificationService->logEvent(
            $this->notification,
            NotificationLog::EVENT_FAILED,
            [
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
                'final_failure' => true,
            ]
        );
    }
}

