<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeder pour les templates de notifications par défaut
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * Exécuter le seeder
     *
     * @return void
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Rappel de paiement - Tranche 1',
                'type' => 'payment_reminder',
                'subject' => 'Rappel de paiement - Tranche {{tranche}} - {{student_name}}',
                'body' => "Bonjour {{recipient_name}},\n\n" .
                    "Nous vous rappelons que le paiement de la tranche {{tranche}} des frais de scolarité pour {{student_name}} est dû.\n\n" .
                    "Montant à payer : {{amount}} €\n" .
                    "Date d'échéance : {{due_date}}\n\n" .
                    "Merci de procéder au règlement dans les plus brefs délais.\n\n" .
                    "Cordialement,\n" .
                    "L'équipe administrative",
                'variables' => [
                    'recipient_name',
                    'student_name',
                    'amount',
                    'due_date',
                    'tranche',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Information urgente',
                'type' => 'urgent_info',
                'subject' => 'Information urgente concernant {{student_name}}',
                'body' => "Bonjour {{recipient_name}},\n\n" .
                    "Nous vous contactons concernant une information urgente relative à {{student_name}}.\n\n" .
                    "Type d'urgence : {{urgency_type}}\n\n" .
                    "Message :\n{{message}}\n\n" .
                    "Merci de prendre contact avec nous au plus vite.\n\n" .
                    "Cordialement,\n" .
                    "L'équipe administrative",
                'variables' => [
                    'recipient_name',
                    'student_name',
                    'urgency_type',
                    'message',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Notification générale',
                'type' => 'general',
                'subject' => '{{subject}}',
                'body' => "Bonjour {{recipient_name}},\n\n" .
                    "{{message}}\n\n" .
                    "Cordialement,\n" .
                    "L'équipe administrative",
                'variables' => [
                    'recipient_name',
                    'subject',
                    'message',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('Templates de notifications créés avec succès !');
    }
}

