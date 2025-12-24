# 📋 Référence de Configuration `.env`

Ce fichier sert de référence pour configurer le fichier `.env` du backend. Copiez ces configurations dans votre fichier `.env` et adaptez-les selon votre environnement.

## Configuration Minimale Requise

```env
# ============================================
# APPLICATION
# ============================================
APP_NAME="Plateforme Scolaire"
APP_ENV=local
APP_KEY=base64:... # Généré avec: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# ============================================
# BASE DE DONNÉES
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_platform
DB_USERNAME=root
DB_PASSWORD=

# ============================================
# REDIS (OBLIGATOIRE pour les notifications)
# ============================================
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# ============================================
# EMAIL (OBLIGATOIRE pour les notifications)
# ============================================
# Option 1: Mailtrap (Développement - RECOMMANDÉ)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school-platform.test
MAIL_FROM_NAME="Plateforme Scolaire"

# Option 2: Mailgun (Production)
# MAIL_MAILER=mailgun
# MAILGUN_DOMAIN=votre-domaine.mailgun.org
# MAILGUN_SECRET=votre_secret_key
# MAILGUN_ENDPOINT=api.mailgun.net
# MAIL_FROM_ADDRESS=noreply@votre-domaine.com
# MAIL_FROM_NAME="Plateforme Scolaire"

# Option 3: SendGrid (Production)
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.sendgrid.net
# MAIL_PORT=587
# MAIL_USERNAME=apikey
# MAIL_PASSWORD=votre_api_key_sendgrid
# MAIL_ENCRYPTION=tls
# MAIL_FROM_ADDRESS=noreply@votre-domaine.com
# MAIL_FROM_NAME="Plateforme Scolaire"

# Option 4: Log uniquement (Tests - n'envoie pas d'emails)
# MAIL_MAILER=log
```

## Configuration Complète (Tous les paramètres)

```env
# ============================================
# APPLICATION
# ============================================
APP_NAME="Plateforme Scolaire"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

# ============================================
# LOGGING
# ============================================
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ============================================
# BASE DE DONNÉES
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_platform
DB_USERNAME=root
DB_PASSWORD=

# ============================================
# SESSION
# ============================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# ============================================
# QUEUE & REDIS (OBLIGATOIRE pour notifications)
# ============================================
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# ============================================
# EMAIL (OBLIGATOIRE pour notifications)
# ============================================
# Configuration Mailtrap (Développement)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@school-platform.test
MAIL_FROM_NAME="Plateforme Scolaire"

# ============================================
# CACHE
# ============================================
CACHE_STORE=redis
CACHE_PREFIX=

# ============================================
# FILESYSTEM
# ============================================
FILESYSTEM_DISK=local

# ============================================
# AWS (Optionnel)
# ============================================
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ============================================
# VITE (Frontend)
# ============================================
VITE_APP_NAME="${APP_NAME}"
```

## Guide d'Obtainment des Credentials

### Mailtrap (Développement)

1. Créez un compte gratuit sur [mailtrap.io](https://mailtrap.io)
2. Créez une nouvelle "Inbox" pour votre projet
3. Allez dans "SMTP Settings"
4. Sélectionnez "Laravel" comme framework
5. Copiez les credentials :
   - `MAIL_HOST` : `smtp.mailtrap.io`
   - `MAIL_PORT` : `2525`
   - `MAIL_USERNAME` : Votre username (ex: `abc123def456`)
   - `MAIL_PASSWORD` : Votre password (ex: `xyz789uvw012`)

### Mailgun (Production)

1. Créez un compte sur [mailgun.com](https://www.mailgun.com)
2. Vérifiez votre domaine
3. Allez dans "Sending" > "Domain Settings"
4. Copiez :
   - `MAILGUN_DOMAIN` : Votre domaine (ex: `mg.votre-domaine.com`)
   - `MAILGUN_SECRET` : Votre API Key privée

### SendGrid (Production)

1. Créez un compte sur [sendgrid.com](https://sendgrid.com)
2. Allez dans "Settings" > "API Keys"
3. Créez une nouvelle API Key avec les permissions "Mail Send"
4. Copiez l'API Key dans `MAIL_PASSWORD`
5. Utilisez `apikey` comme `MAIL_USERNAME`

## Vérification de la Configuration

### Test Redis

```bash
redis-cli ping
# Devrait répondre: PONG
```

### Test Email (avec Mailtrap)

1. Configurez Mailtrap dans `.env`
2. Créez une notification via l'API
3. Vérifiez votre inbox Mailtrap - l'email devrait apparaître

### Test Queue

```bash
# Vérifier que le worker traite les jobs
php artisan queue:work redis --queue=notifications --verbose
```

## Notes Importantes

1. **Ne commitez JAMAIS le fichier `.env`** - Il contient des informations sensibles
2. Le fichier `.env` est déjà dans `.gitignore`
3. Utilisez `.env.example` comme template (s'il existe) ou ce fichier comme référence
4. En production, utilisez des variables d'environnement sécurisées
5. Changez `APP_DEBUG=false` en production
6. Utilisez une clé `APP_KEY` unique et sécurisée

## Support

Pour toute question sur la configuration, consultez :
- `FRONTEND_SETUP_GUIDE.md` - Guide complet de configuration
- `API_NOTIFICATIONS.md` - Documentation de l'API
- `NOTIFICATIONS_MODULE.md` - Documentation technique

