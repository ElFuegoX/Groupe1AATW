# 📧 Module de Notifications - Documentation Technique

## Vue d'ensemble

Module complet de gestion des notifications par email pour la plateforme de gestion scolaire. Le système permet d'envoyer des notifications asynchrones aux parents d'élèves avec suivi complet des envois, ouvertures et clics.

## Architecture

### Structure des fichiers

```
backend/
├── app/
│   ├── Models/
│   │   ├── Notification.php              # Modèle principal des notifications
│   │   ├── NotificationLog.php           # Logs des événements (envoi, ouverture, clic)
│   │   └── NotificationTemplate.php      # Templates de notifications
│   ├── Services/
│   │   └── NotificationService.php       # Service principal avec logique métier
│   ├── Jobs/
│   │   └── SendNotificationJob.php       # Job asynchrone pour l'envoi
│   ├── Mail/
│   │   └── NotificationMail.php          # Mailable pour les emails
│   └── Http/Controllers/Api/
│       └── NotificationController.php    # Controller API REST
├── database/
│   ├── migrations/
│   │   ├── 2025_01_17_100000_create_notification_templates_table.php
│   │   ├── 2025_01_17_100001_create_notifications_table.php
│   │   └── 2025_01_17_100002_create_notification_logs_table.php
│   └── seeders/
│       └── NotificationTemplateSeeder.php
├── resources/views/emails/
│   └── notification.blade.php            # Template Blade pour les emails
└── routes/
    └── api.php                           # Routes API (section notifications)
```

## Installation

### 1. Exécuter les migrations

```bash
cd backend
php artisan migrate
```

### 2. Seed des templates par défaut

```bash
php artisan db:seed --class=NotificationTemplateSeeder
```

Ou pour seed toute la base :

```bash
php artisan db:seed
```

### 3. Configuration de la queue

Dans le fichier `.env`, configurez la queue pour utiliser Redis :

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 4. Configuration de l'email

Configurez votre service d'email dans `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school-platform.com
MAIL_FROM_NAME="Plateforme Scolaire"
```

### 5. Démarrer le worker de queue

```bash
php artisan queue:work redis --queue=notifications
```

Ou pour le développement avec auto-reload :

```bash
php artisan queue:listen redis --queue=notifications
```

## Utilisation

### Via le Service

```php
use App\Services\NotificationService;

$service = app(NotificationService::class);

// Envoyer un rappel de paiement
$notification = $service->sendPaymentReminder(
    recipientEmail: 'parent@example.com',
    recipientName: 'Marie Dupont',
    data: [
        'student_name' => 'Jean Dupont',
        'amount' => '500',
        'due_date' => '2025-02-01',
        'tranche' => '1',
    ]
);

// Envoyer une notification urgente
$notification = $service->sendUrgentNotification(
    recipientEmail: 'parent@example.com',
    recipientName: 'Marie Dupont',
    data: [
        'student_name' => 'Jean Dupont',
        'urgency_type' => 'Absence',
        'message' => 'Votre enfant est absent depuis ce matin.',
    ]
);

// Programmer une notification
$scheduledAt = now()->addDays(2);
$notification = $service->sendGeneralNotification(
    recipientEmail: 'parent@example.com',
    recipientName: 'Marie Dupont',
    data: ['message' => 'Réunion prévue'],
    scheduledAt: $scheduledAt
);
```

### Via l'API REST

Voir le fichier `API_NOTIFICATIONS.md` pour la documentation complète de l'API.

## Fonctionnalités

### ✅ Implémenté

- [x] Modèles Eloquent avec relations
- [x] Migrations pour les 3 tables
- [x] Service principal avec méthodes métier
- [x] Job asynchrone avec retry logic
- [x] Mailable avec template Blade responsive
- [x] Controller API REST complet
- [x] Routes API avec authentification Sanctum
- [x] Seeders pour templates par défaut
- [x] Tests unitaires
- [x] Logging complet des événements
- [x] Gestion des statuts (draft, scheduled, sent, failed)
- [x] Suivi des ouvertures et clics
- [x] Système de retry automatique
- [x] Templates configurables en base de données
- [x] Remplacement automatique des variables

### 🔄 À venir (intégrations futures)

- [ ] Webhooks pour suivi en temps réel
- [ ] Support SMS (via Twilio)
- [ ] Support WhatsApp
- [ ] Dashboard de statistiques
- [ ] Export des logs
- [ ] Templates personnalisables par établissement

## Structure de la base de données

### Table `notification_templates`

Stoque les templates de notifications réutilisables.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | ID unique |
| `name` | string | Nom du template (unique) |
| `type` | string | Type : `payment_reminder`, `urgent_info`, `general` |
| `subject` | string | Sujet de l'email (avec variables) |
| `body` | text | Corps de l'email (avec variables) |
| `variables` | json | Liste des variables disponibles |
| `is_active` | boolean | Template actif ou non |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de mise à jour |

### Table `notifications`

Stoque les notifications individuelles.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | ID unique |
| `template_id` | bigint | Référence au template (nullable) |
| `type` | string | Type de notification |
| `status` | string | Statut : `draft`, `scheduled`, `sent`, `failed` |
| `recipient_email` | string | Email du destinataire |
| `recipient_name` | string | Nom du destinataire |
| `subject` | string | Sujet final (variables remplacées) |
| `body` | text | Corps final (variables remplacées) |
| `variables` | json | Variables utilisées |
| `scheduled_at` | timestamp | Date d'envoi programmée |
| `sent_at` | timestamp | Date d'envoi effective |
| `retry_count` | integer | Nombre de tentatives |
| `error_message` | text | Message d'erreur si échec |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de mise à jour |

### Table `notification_logs`

Stoque tous les événements liés aux notifications.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | ID unique |
| `notification_id` | bigint | Référence à la notification |
| `event` | string | Type d'événement : `sent`, `opened`, `clicked`, `failed`, `bounced` |
| `details` | text | Détails supplémentaires (JSON) |
| `ip_address` | string | IP pour opened/clicked |
| `user_agent` | string | User agent pour opened/clicked |
| `occurred_at` | timestamp | Date de l'événement |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de mise à jour |

## Tests

Exécuter les tests unitaires :

```bash
php artisan test --filter NotificationServiceTest
```

Ou tous les tests :

```bash
php artisan test
```

## Monitoring

### Laravel Horizon (optionnel)

Pour un monitoring avancé des queues, installez Laravel Horizon :

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Puis accédez à `/horizon` pour le dashboard.

### Logs

Les logs sont disponibles dans `storage/logs/laravel.log`. Le module logge :
- Création de notifications
- Envoi d'emails
- Échecs et retries
- Événements de suivi

## Sécurité

- ✅ Validation complète des inputs
- ✅ Sanitization des données
- ✅ Authentification requise (Sanctum)
- ✅ Protection CSRF pour les routes web
- ✅ Échappement des variables dans les templates

## Performance

- ✅ Envoi asynchrone via queues
- ✅ Index sur les colonnes fréquemment requêtées
- ✅ Pagination pour les listes
- ✅ Eager loading des relations

## Maintenance

### Nettoyer les anciennes notifications

Créer une commande Artisan pour nettoyer les notifications anciennes :

```bash
php artisan make:command CleanOldNotifications
```

### Surveiller les échecs

Vérifier régulièrement la table `failed_jobs` :

```bash
php artisan queue:failed
```

Relancer les jobs échoués :

```bash
php artisan queue:retry all
```

## Support et contribution

Pour toute question ou amélioration, contactez l'équipe backend.

## Licence

Propriétaire - Plateforme de Gestion Scolaire

