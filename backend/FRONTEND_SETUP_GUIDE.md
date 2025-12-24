# 🚀 Guide de Configuration - Équipe Frontend

## Vue d'ensemble

Ce guide vous aide à comprendre la configuration backend nécessaire pour que le module de notifications fonctionne correctement. Bien que vous travailliez sur le frontend React, il est important de connaître les prérequis backend pour tester et intégrer l'API.

## ⚙️ Configuration Backend Requise

### 1. Fichier `.env` - Configuration de base

Le fichier `.env` dans le dossier `backend/` doit contenir les configurations suivantes pour que le module de notifications fonctionne.

#### Configuration de la Queue (Redis)

Le module utilise Redis pour gérer les queues asynchrones. **Sans cette configuration, les notifications ne seront pas envoyées.**

```env
# Queue Configuration
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

**⚠️ Important :** 
- Si Redis n'est pas disponible, vous pouvez temporairement utiliser `QUEUE_CONNECTION=database` pour le développement, mais cela est moins performant.
- Pour la production, Redis est **obligatoire**.

#### Configuration de l'Email

Le module envoie des emails via SMTP. Voici les configurations pour différents environnements :

##### Pour le développement (Mailtrap - Recommandé)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school-platform.test
MAIL_FROM_NAME="Plateforme Scolaire"
```

**Comment obtenir les credentials Mailtrap :**
1. Créez un compte gratuit sur [mailtrap.io](https://mailtrap.io)
2. Créez une "Inbox" pour votre projet
3. Copiez les credentials SMTP depuis l'onglet "SMTP Settings"
4. Collez-les dans votre `.env`

##### Pour la production (Mailgun)

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=votre-domaine.mailgun.org
MAILGUN_SECRET=votre_secret_key
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Plateforme Scolaire"
```

##### Pour la production (SendGrid)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre_api_key_sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Plateforme Scolaire"
```

#### Configuration de la Base de Données

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_platform
DB_USERNAME=root
DB_PASSWORD=
```

#### Configuration de l'Application

```env
APP_NAME="Plateforme Scolaire"
APP_ENV=local
APP_KEY=base64:... (généré automatiquement)
APP_DEBUG=true
APP_URL=http://localhost:8000
```

### 2. Installation des Dépendances

Avant de commencer, l'équipe backend doit exécuter :

```bash
cd backend
composer install
```

### 3. Configuration de la Base de Données

```bash
# Créer le fichier .env s'il n'existe pas
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Seed les templates de notifications
php artisan db:seed --class=NotificationTemplateSeeder
```

### 4. Démarrer les Services

#### Démarrer le serveur Laravel

```bash
php artisan serve
```

Le serveur sera accessible sur `http://localhost:8000`

#### Démarrer Redis (si pas déjà démarré)

**Windows :**
- Téléchargez Redis depuis [redis.io](https://redis.io/download) ou utilisez WSL
- Ou utilisez Docker : `docker run -d -p 6379:6379 redis`

**Linux/Mac :**
```bash
redis-server
```

**Vérifier que Redis fonctionne :**
```bash
redis-cli ping
# Devrait répondre : PONG
```

#### Démarrer le Worker de Queue

**⚠️ CRITIQUE :** Sans ce worker, les notifications ne seront **jamais envoyées** !

```bash
php artisan queue:work redis --queue=notifications
```

Pour le développement avec auto-reload :
```bash
php artisan queue:listen redis --queue=notifications
```

**Note :** Ce processus doit rester actif en arrière-plan. Utilisez un terminal séparé ou un gestionnaire de processus comme Supervisor en production.

## 🧪 Tester la Configuration

### Test 1 : Vérifier que l'API répond

```bash
curl http://localhost:8000/api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Test 2 : Créer une notification de test

```bash
curl -X POST http://localhost:8000/api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "general",
    "recipient_email": "test@example.com",
    "recipient_name": "Test User",
    "data": {
      "subject": "Test",
      "message": "Ceci est un test"
    }
  }'
```

### Test 3 : Vérifier que le worker traite les jobs

Après avoir créé une notification, vérifiez dans le terminal du worker que vous voyez :
```
Processing: App\Jobs\SendNotificationJob
Processed:  App\Jobs\SendNotificationJob
```

## 🔍 Dépannage

### Problème : Les notifications ne sont pas envoyées

**Solutions :**
1. ✅ Vérifiez que le worker de queue est démarré : `php artisan queue:work redis --queue=notifications`
2. ✅ Vérifiez que Redis fonctionne : `redis-cli ping`
3. ✅ Vérifiez la configuration dans `.env` : `QUEUE_CONNECTION=redis`
4. ✅ Vérifiez les logs : `tail -f storage/logs/laravel.log`

### Problème : Erreur "Connection refused" pour Redis

**Solutions :**
1. Vérifiez que Redis est démarré
2. Vérifiez les credentials dans `.env` : `REDIS_HOST`, `REDIS_PORT`
3. Testez la connexion : `redis-cli -h 127.0.0.1 -p 6379 ping`

### Problème : Erreur d'envoi d'email

**Solutions :**
1. Vérifiez les credentials SMTP dans `.env`
2. Testez avec Mailtrap en développement
3. Vérifiez les logs : `storage/logs/laravel.log`
4. Utilisez `MAIL_MAILER=log` pour voir les emails dans les logs sans les envoyer

### Problème : Erreur 401 (Non autorisé)

**Solutions :**
1. Vérifiez que vous incluez le token dans l'en-tête : `Authorization: Bearer {token}`
2. Vérifiez que le token est valide et non expiré
3. Vérifiez que Sanctum est correctement configuré

## 📊 Monitoring

### Vérifier les jobs en attente

```bash
php artisan queue:monitor redis:notifications
```

### Vérifier les jobs échoués

```bash
php artisan queue:failed
```

### Relancer un job échoué

```bash
php artisan queue:retry {job_id}
```

## 🔐 Variables d'Environnement Importantes

| Variable | Description | Exemple |
|----------|-------------|---------|
| `QUEUE_CONNECTION` | Driver de queue (redis/database) | `redis` |
| `REDIS_HOST` | Adresse du serveur Redis | `127.0.0.1` |
| `REDIS_PORT` | Port Redis | `6379` |
| `MAIL_MAILER` | Driver d'email | `smtp` |
| `MAIL_HOST` | Serveur SMTP | `smtp.mailtrap.io` |
| `MAIL_PORT` | Port SMTP | `2525` |
| `MAIL_USERNAME` | Username SMTP | `votre_username` |
| `MAIL_PASSWORD` | Password SMTP | `votre_password` |
| `MAIL_FROM_ADDRESS` | Email expéditeur | `noreply@school.com` |
| `APP_URL` | URL de l'application | `http://localhost:8000` |

## 📝 Checklist de Configuration

Avant de commencer à intégrer l'API dans votre frontend React, vérifiez que :

- [ ] Le fichier `.env` est configuré avec Redis (voir `ENV_CONFIGURATION_REFERENCE.md`)
- [ ] Le fichier `.env` est configuré avec les credentials email (voir `ENV_CONFIGURATION_REFERENCE.md`)
- [ ] Redis est installé et démarré
- [ ] Les migrations sont exécutées : `php artisan migrate`
- [ ] Les templates sont seedés : `php artisan db:seed --class=NotificationTemplateSeeder`
- [ ] Le worker de queue est démarré : `php artisan queue:work redis --queue=notifications`
- [ ] Le serveur Laravel est démarré : `php artisan serve`
- [ ] Vous avez un token d'authentification Sanctum valide
- [ ] Vous pouvez accéder à l'API : `GET /api/notifications`

**📋 Référence complète :** Consultez `ENV_CONFIGURATION_REFERENCE.md` pour tous les paramètres de configuration disponibles.

## 🎯 Prochaines Étapes

Une fois la configuration backend validée :

1. ✅ Lisez la documentation API : `API_NOTIFICATIONS.md`
2. ✅ Consultez la référence de configuration : `ENV_CONFIGURATION_REFERENCE.md`
3. ✅ Testez les endpoints avec Postman/Insomnia
4. ✅ Intégrez l'API dans votre application React
5. ✅ Gérez les erreurs et les cas limites
6. ✅ Implémentez le polling ou WebSockets pour les mises à jour en temps réel

## 📚 Documentation Disponible

- **`API_NOTIFICATIONS.md`** - Documentation complète de l'API REST
- **`FRONTEND_SETUP_GUIDE.md`** - Ce guide de configuration
- **`ENV_CONFIGURATION_REFERENCE.md`** - Référence complète des variables `.env`
- **`NOTIFICATIONS_MODULE.md`** - Documentation technique backend

## 📞 Support

Si vous rencontrez des problèmes de configuration :

1. Vérifiez les logs : `backend/storage/logs/laravel.log`
2. Contactez l'équipe backend
3. Consultez la documentation Laravel : [laravel.com/docs](https://laravel.com/docs)

---

**Note pour l'équipe frontend :** Vous n'avez pas besoin de modifier ces configurations vous-même, mais il est important de comprendre les prérequis pour tester l'API. En cas de problème, contactez l'équipe backend qui pourra vérifier ces configurations.

