# 📧 Documentation API - Module de Notifications

## Vue d'ensemble

Cette documentation décrit l'API REST pour le module de notifications de la plateforme de gestion scolaire. Le module permet d'envoyer des notifications par email aux parents d'élèves (rappels de paiement, informations urgentes, notifications générales).

## Authentification

Toutes les routes nécessitent une authentification via **Laravel Sanctum**. Vous devez inclure le token d'authentification dans l'en-tête de chaque requête :

```
Authorization: Bearer {votre_token}
```

## Base URL

```
http://votre-domaine.com/api
```

## Endpoints

### 1. Liste des notifications

Récupère la liste paginée des notifications avec possibilité de filtrage.

**GET** `/notifications`

#### Paramètres de requête (optionnels)

| Paramètre | Type | Description |
|-----------|------|-------------|
| `status` | string | Filtrer par statut : `draft`, `scheduled`, `sent`, `failed` |
| `type` | string | Filtrer par type : `payment_reminder`, `urgent_info`, `general` |
| `recipient_email` | string | Filtrer par email du destinataire |
| `per_page` | integer | Nombre d'éléments par page (1-100, défaut: 15) |
| `page` | integer | Numéro de page (défaut: 1) |

#### Exemple de requête

```bash
GET /api/notifications?status=sent&type=payment_reminder&per_page=20
```

#### Exemple de réponse

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "template_id": 1,
      "type": "payment_reminder",
      "status": "sent",
      "recipient_email": "parent@example.com",
      "recipient_name": "Marie Dupont",
      "subject": "Rappel de paiement - Tranche 1 - Jean Dupont",
      "body": "Bonjour Marie Dupont,\n\nNous vous rappelons...",
      "variables": {
        "student_name": "Jean Dupont",
        "amount": "500",
        "due_date": "2025-02-01",
        "tranche": "1"
      },
      "scheduled_at": null,
      "sent_at": "2025-01-17T10:30:00.000000Z",
      "retry_count": 0,
      "error_message": null,
      "created_at": "2025-01-17T10:25:00.000000Z",
      "updated_at": "2025-01-17T10:30:00.000000Z",
      "template": {
        "id": 1,
        "name": "Rappel de paiement - Tranche 1",
        "type": "payment_reminder"
      },
      "logs": []
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

---

### 2. Créer une notification

Crée une nouvelle notification et la dispatch pour envoi.

**POST** `/notifications`

#### Corps de la requête

```json
{
  "type": "payment_reminder",
  "recipient_email": "parent@example.com",
  "recipient_name": "Marie Dupont",
  "data": {
    "student_name": "Jean Dupont",
    "amount": "500",
    "due_date": "2025-02-01",
    "tranche": "1"
  },
  "scheduled_at": "2025-01-20T10:00:00Z"
}
```

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `type` | string | Oui | Type de notification : `payment_reminder`, `urgent_info`, `general` |
| `recipient_email` | string (email) | Oui | Email du destinataire |
| `recipient_name` | string | Oui | Nom du destinataire |
| `data` | object | Non | Données pour remplacer les variables du template |
| `scheduled_at` | string (datetime) | Non | Date d'envoi programmée (ISO 8601). Si omis, envoi immédiat |

#### Types de notifications et données requises

##### `payment_reminder` (Rappel de paiement)

```json
{
  "type": "payment_reminder",
  "recipient_email": "parent@example.com",
  "recipient_name": "Marie Dupont",
  "data": {
    "student_name": "Jean Dupont",
    "amount": "500",
    "due_date": "2025-02-01",
    "tranche": "1"
  }
}
```

##### `urgent_info` (Information urgente)

```json
{
  "type": "urgent_info",
  "recipient_email": "parent@example.com",
  "recipient_name": "Marie Dupont",
  "data": {
    "student_name": "Jean Dupont",
    "urgency_type": "Absence",
    "message": "Votre enfant est absent depuis ce matin."
  }
}
```

##### `general` (Notification générale)

```json
{
  "type": "general",
  "recipient_email": "parent@example.com",
  "recipient_name": "Marie Dupont",
  "data": {
    "subject": "Réunion parents-professeurs",
    "message": "Une réunion est prévue le 15 février à 18h."
  }
}
```

#### Exemple de réponse (succès)

```json
{
  "success": true,
  "message": "Notification créée avec succès",
  "data": {
    "id": 1,
    "type": "payment_reminder",
    "status": "scheduled",
    "recipient_email": "parent@example.com",
    "subject": "Rappel de paiement - Tranche 1 - Jean Dupont",
    "created_at": "2025-01-17T10:25:00.000000Z"
  }
}
```

#### Exemple de réponse (erreur)

```json
{
  "success": false,
  "errors": {
    "recipient_email": ["Le champ recipient email doit être une adresse email valide."],
    "type": ["Le champ type est obligatoire."]
  }
}
```

---

### 3. Afficher une notification

Récupère les détails d'une notification spécifique avec ses statistiques.

**GET** `/notifications/{id}`

#### Exemple de requête

```bash
GET /api/notifications/1
```

#### Exemple de réponse

```json
{
  "success": true,
  "data": {
    "id": 1,
    "type": "payment_reminder",
    "status": "sent",
    "recipient_email": "parent@example.com",
    "subject": "Rappel de paiement - Tranche 1 - Jean Dupont",
    "sent_at": "2025-01-17T10:30:00.000000Z",
    "template": { ... },
    "logs": [ ... ]
  },
  "stats": {
    "sent": 1,
    "opened": 1,
    "clicked": 0,
    "failed": 0,
    "bounced": 0,
    "last_opened_at": "2025-01-17T11:00:00.000000Z",
    "last_clicked_at": null
  }
}
```

---

### 4. Mettre à jour une notification

Met à jour une notification (uniquement si elle est en statut `draft`).

**PUT** `/notifications/{id}`

#### Corps de la requête

```json
{
  "recipient_email": "nouveau@example.com",
  "recipient_name": "Nouveau Nom",
  "subject": "Nouveau sujet",
  "body": "Nouveau contenu",
  "scheduled_at": "2025-01-20T10:00:00Z"
}
```

#### Paramètres (tous optionnels)

| Paramètre | Type | Description |
|-----------|------|-------------|
| `recipient_email` | string (email) | Nouvel email du destinataire |
| `recipient_name` | string | Nouveau nom du destinataire |
| `subject` | string | Nouveau sujet |
| `body` | string | Nouveau contenu |
| `scheduled_at` | string (datetime) | Nouvelle date d'envoi programmée |

---

### 5. Supprimer une notification

Supprime une notification (uniquement si elle est en statut `draft` ou `failed`).

**DELETE** `/notifications/{id}`

#### Exemple de réponse

```json
{
  "success": true,
  "message": "Notification supprimée avec succès"
}
```

---

### 6. Statistiques d'une notification

Récupère les statistiques d'ouverture et de clics d'une notification.

**GET** `/notifications/{id}/stats`

#### Exemple de réponse

```json
{
  "success": true,
  "data": {
    "sent": 1,
    "opened": 1,
    "clicked": 0,
    "failed": 0,
    "bounced": 0,
    "last_opened_at": "2025-01-17T11:00:00.000000Z",
    "last_clicked_at": null
  }
}
```

---

### 7. Relancer une notification échouée

Relance l'envoi d'une notification qui a échoué.

**POST** `/notifications/{id}/retry`

#### Exemple de réponse

```json
{
  "success": true,
  "message": "Notification relancée avec succès",
  "data": {
    "id": 1,
    "status": "scheduled",
    ...
  }
}
```

---

## Statuts des notifications

| Statut | Description |
|--------|-------------|
| `draft` | Brouillon, pas encore envoyée |
| `scheduled` | Programmée pour envoi |
| `sent` | Envoyée avec succès |
| `failed` | Échec d'envoi |

## Types de notifications

| Type | Description |
|------|-------------|
| `payment_reminder` | Rappel de paiement des frais de scolarité |
| `urgent_info` | Information urgente concernant un élève |
| `general` | Notification générale de l'école |

## Codes de réponse HTTP

| Code | Description |
|------|-------------|
| `200` | Succès |
| `201` | Créé avec succès |
| `404` | Ressource non trouvée |
| `422` | Erreur de validation |
| `500` | Erreur serveur |

## Exemples d'utilisation avec React

### Créer un rappel de paiement

```javascript
const createPaymentReminder = async (parentEmail, parentName, studentData) => {
  const response = await fetch('/api/notifications', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({
      type: 'payment_reminder',
      recipient_email: parentEmail,
      recipient_name: parentName,
      data: {
        student_name: studentData.name,
        amount: studentData.amount,
        due_date: studentData.dueDate,
        tranche: studentData.tranche,
      },
    }),
  });

  return await response.json();
};
```

### Récupérer la liste des notifications

```javascript
const getNotifications = async (filters = {}) => {
  const params = new URLSearchParams(filters);
  const response = await fetch(`/api/notifications?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });

  return await response.json();
};
```

## Notes importantes

1. **Queues** : Les notifications sont envoyées de manière asynchrone via des queues. L'envoi peut prendre quelques secondes.

2. **Templates** : Les templates sont gérés en base de données et peuvent être modifiés par les administrateurs.

3. **Variables** : Les variables dans les templates sont remplacées automatiquement lors de la création de la notification.

4. **Retry** : En cas d'échec, le système réessaie automatiquement jusqu'à 3 fois avec des délais progressifs.

5. **Logging** : Tous les événements (envoi, ouverture, clic, échec) sont loggés pour le suivi.

## Configuration Backend Requise

⚠️ **Important pour l'équipe frontend :** Avant d'utiliser cette API, assurez-vous que le backend est correctement configuré.

### Prérequis

1. **Redis** doit être installé et démarré (pour les queues asynchrones)
2. **Configuration email** doit être configurée dans le fichier `.env` du backend
3. **Worker de queue** doit être actif : `php artisan queue:work redis --queue=notifications`
4. **Migrations** doivent être exécutées : `php artisan migrate`
5. **Templates** doivent être seedés : `php artisan db:seed --class=NotificationTemplateSeeder`

### Configuration du fichier `.env`

Le fichier `.env` du backend doit contenir au minimum :

```env
# Queue Configuration (OBLIGATOIRE)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Email Configuration (OBLIGATOIRE)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Pour le développement
MAIL_PORT=2525
MAIL_USERNAME=votre_username
MAIL_PASSWORD=votre_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school-platform.test
MAIL_FROM_NAME="Plateforme Scolaire"
```

**📖 Documentation complète :**
- **`FRONTEND_SETUP_GUIDE.md`** - Guide détaillé avec instructions pas à pas, dépannage et tests
- **`ENV_CONFIGURATION_REFERENCE.md`** - Référence complète de toutes les variables `.env` avec exemples pour différents environnements (dev, production)

### Vérification rapide

Pour vérifier que tout est configuré correctement :

```bash
# 1. Vérifier que Redis fonctionne
redis-cli ping  # Devrait répondre "PONG"

# 2. Vérifier que le worker est actif
# Vous devriez voir un processus "php artisan queue:work"

# 3. Tester l'API
curl http://localhost:8000/api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Support

Pour toute question ou problème :
1. Consultez **`README_NOTIFICATIONS.md`** pour un guide de démarrage rapide
2. Consultez **`FRONTEND_SETUP_GUIDE.md`** pour la configuration
3. Consultez **`ENV_CONFIGURATION_REFERENCE.md`** pour les variables `.env`
4. Consultez **`NOTIFICATIONS_MODULE.md`** pour la documentation technique
5. Contactez l'équipe backend

