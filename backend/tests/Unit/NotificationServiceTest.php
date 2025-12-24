<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitaires pour NotificationService
 */
class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    /**
     * Test de création d'un rappel de paiement
     */
    public function test_send_payment_reminder(): void
    {
        // Créer un template de rappel de paiement
        $template = NotificationTemplate::create([
            'name' => 'Rappel de paiement',
            'type' => Notification::TYPE_PAYMENT_REMINDER,
            'subject' => 'Rappel de paiement - {{student_name}}',
            'body' => 'Bonjour {{recipient_name}}, le montant de {{amount}} € est dû pour {{student_name}}.',
            'variables' => ['recipient_name', 'student_name', 'amount'],
            'is_active' => true,
        ]);

        $data = [
            'student_name' => 'Jean Dupont',
            'amount' => '500',
            'due_date' => '2025-02-01',
            'tranche' => '1',
        ];

        $notification = $this->service->sendPaymentReminder(
            'parent@example.com',
            'Marie Dupont',
            $data
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_PAYMENT_REMINDER, $notification->type);
        $this->assertEquals('parent@example.com', $notification->recipient_email);
        $this->assertEquals('Marie Dupont', $notification->recipient_name);
        $this->assertStringContainsString('Jean Dupont', $notification->subject);
        $this->assertStringContainsString('Marie Dupont', $notification->body);
        $this->assertStringContainsString('500', $notification->body);
    }

    /**
     * Test de création d'une notification urgente
     */
    public function test_send_urgent_notification(): void
    {
        // Créer un template de notification urgente
        $template = NotificationTemplate::create([
            'name' => 'Information urgente',
            'type' => Notification::TYPE_URGENT_INFO,
            'subject' => 'Information urgente - {{student_name}}',
            'body' => 'Bonjour {{recipient_name}}, {{message}} concernant {{student_name}}.',
            'variables' => ['recipient_name', 'student_name', 'message'],
            'is_active' => true,
        ]);

        $data = [
            'student_name' => 'Jean Dupont',
            'urgency_type' => 'Absence',
            'message' => 'Votre enfant est absent depuis ce matin.',
        ];

        $notification = $this->service->sendUrgentNotification(
            'parent@example.com',
            'Marie Dupont',
            $data
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_URGENT_INFO, $notification->type);
        $this->assertStringContainsString('Marie Dupont', $notification->body);
        $this->assertStringContainsString('Votre enfant est absent', $notification->body);
    }

    /**
     * Test de création d'une notification générale
     */
    public function test_send_general_notification(): void
    {
        // Créer un template de notification générale
        $template = NotificationTemplate::create([
            'name' => 'Notification générale',
            'type' => Notification::TYPE_GENERAL,
            'subject' => '{{subject}}',
            'body' => 'Bonjour {{recipient_name}}, {{message}}',
            'variables' => ['recipient_name', 'subject', 'message'],
            'is_active' => true,
        ]);

        $data = [
            'subject' => 'Réunion parents-professeurs',
            'message' => 'Une réunion est prévue le 15 février.',
        ];

        $notification = $this->service->sendGeneralNotification(
            'parent@example.com',
            'Marie Dupont',
            $data
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_GENERAL, $notification->type);
        $this->assertStringContainsString('Réunion parents-professeurs', $notification->subject);
    }

    /**
     * Test d'échec si le template n'existe pas
     */
    public function test_fails_when_template_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template de rappel de paiement non trouvé ou inactif');

        $this->service->sendPaymentReminder(
            'parent@example.com',
            'Marie Dupont',
            []
        );
    }

    /**
     * Test de programmation d'une notification
     */
    public function test_scheduled_notification(): void
    {
        $template = NotificationTemplate::create([
            'name' => 'Rappel de paiement',
            'type' => Notification::TYPE_PAYMENT_REMINDER,
            'subject' => 'Rappel',
            'body' => 'Message',
            'variables' => [],
            'is_active' => true,
        ]);

        $scheduledAt = now()->addDays(2);

        $notification = $this->service->sendPaymentReminder(
            'parent@example.com',
            'Marie Dupont',
            [],
            $scheduledAt
        );

        $this->assertEquals(Notification::STATUS_SCHEDULED, $notification->status);
        $this->assertEquals($scheduledAt->format('Y-m-d H:i:s'), $notification->scheduled_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test de logging d'événements
     */
    public function test_log_event(): void
    {
        $template = NotificationTemplate::create([
            'name' => 'Test',
            'type' => Notification::TYPE_GENERAL,
            'subject' => 'Test',
            'body' => 'Test',
            'variables' => [],
            'is_active' => true,
        ]);

        $notification = Notification::create([
            'template_id' => $template->id,
            'type' => Notification::TYPE_GENERAL,
            'status' => Notification::STATUS_DRAFT,
            'recipient_email' => 'test@example.com',
            'subject' => 'Test',
            'body' => 'Test',
        ]);

        $log = $this->service->logEvent(
            $notification,
            NotificationLog::EVENT_SENT,
            ['test' => 'data'],
            '127.0.0.1',
            'Test Agent'
        );

        $this->assertInstanceOf(NotificationLog::class, $log);
        $this->assertEquals($notification->id, $log->notification_id);
        $this->assertEquals(NotificationLog::EVENT_SENT, $log->event);
        $this->assertEquals('127.0.0.1', $log->ip_address);
    }

    /**
     * Test de récupération des statistiques
     */
    public function test_get_notification_stats(): void
    {
        $template = NotificationTemplate::create([
            'name' => 'Test',
            'type' => Notification::TYPE_GENERAL,
            'subject' => 'Test',
            'body' => 'Test',
            'variables' => [],
            'is_active' => true,
        ]);

        $notification = Notification::create([
            'template_id' => $template->id,
            'type' => Notification::TYPE_GENERAL,
            'status' => Notification::STATUS_SENT,
            'recipient_email' => 'test@example.com',
            'subject' => 'Test',
            'body' => 'Test',
        ]);

        // Créer quelques logs
        NotificationLog::create([
            'notification_id' => $notification->id,
            'event' => NotificationLog::EVENT_SENT,
            'occurred_at' => now(),
        ]);

        NotificationLog::create([
            'notification_id' => $notification->id,
            'event' => NotificationLog::EVENT_OPENED,
            'occurred_at' => now(),
        ]);

        $stats = $this->service->getNotificationStats($notification);

        $this->assertIsArray($stats);
        $this->assertEquals(1, $stats['sent']);
        $this->assertEquals(1, $stats['opened']);
        $this->assertEquals(0, $stats['clicked']);
    }

    /**
     * Test de remplacement des variables
     */
    public function test_replace_variables(): void
    {
        $template = NotificationTemplate::create([
            'name' => 'Test',
            'type' => Notification::TYPE_GENERAL,
            'subject' => 'Bonjour {{recipient_name}}',
            'body' => 'Le montant est {{amount}} € pour {{student_name}}.',
            'variables' => ['recipient_name', 'amount', 'student_name'],
            'is_active' => true,
        ]);

        $data = [
            'recipient_name' => 'Marie',
            'amount' => '500',
            'student_name' => 'Jean',
        ];

        $notification = $this->service->sendGeneralNotification(
            'test@example.com',
            'Marie',
            $data
        );

        $this->assertStringContainsString('Marie', $notification->subject);
        $this->assertStringNotContainsString('{{recipient_name}}', $notification->subject);
        $this->assertStringContainsString('500', $notification->body);
        $this->assertStringContainsString('Jean', $notification->body);
    }
}

