# 📧 Module de Notifications - Guide de Démarrage Rapide

## 🎯 Pour l'Équipe Frontend

Bienvenue ! Ce module de notifications est prêt à être intégré dans votre application React. Suivez ce guide pour démarrer rapidement.

## 📚 Documentation Disponible

### 1. **Guide de Configuration** (Commencez ici !)
📄 **`FRONTEND_SETUP_GUIDE.md`**

Guide complet pour configurer le backend avant d'utiliser l'API. Inclut :
- Configuration Redis
- Configuration Email
- Installation et démarrage des services
- Tests de validation
- Dépannage

👉 **À lire en premier si vous configurez l'environnement de développement**

### 2. **Documentation API**
📄 **`API_NOTIFICATIONS.md`**

Documentation complète de l'API REST avec :
- Tous les endpoints disponibles
- Exemples de requêtes/réponses
- Codes d'erreur
- Exemples d'utilisation avec React
- Types de notifications et leurs paramètres

👉 **À lire pour intégrer l'API dans React**

### 3. **Référence de Configuration**
📄 **`ENV_CONFIGURATION_REFERENCE.md`**

Référence complète des variables d'environnement `.env` :
- Configuration minimale requise
- Configuration complète
- Guide d'obtention des credentials (Mailtrap, Mailgun, SendGrid)
- Tests de validation

👉 **À consulter pour configurer le fichier `.env`**

### 4. **Documentation Technique Backend**
📄 **`NOTIFICATIONS_MODULE.md`**

Documentation technique pour l'équipe backend :
- Architecture du module
- Structure de la base de données
- Utilisation du service
- Tests et maintenance

👉 **Pour comprendre le fonctionnement interne (optionnel pour le frontend)**

## 🚀 Démarrage Rapide

### Étape 1 : Configuration Backend

1. Lisez `FRONTEND_SETUP_GUIDE.md` pour configurer l'environnement
2. Consultez `ENV_CONFIGURATION_REFERENCE.md` pour les variables `.env`
3. Vérifiez que Redis et le worker de queue sont démarrés

### Étape 2 : Test de l'API

1. Obtenez un token d'authentification Sanctum
2. Testez l'endpoint : `GET /api/notifications`
3. Créez une notification de test : `POST /api/notifications`

### Étape 3 : Intégration React

1. Lisez `API_NOTIFICATIONS.md` pour comprendre les endpoints
2. Utilisez les exemples de code fournis
3. Implémentez la gestion des erreurs
4. Ajoutez le polling pour les mises à jour en temps réel

## 📋 Checklist Rapide

- [ ] Backend configuré (Redis + Email)
- [ ] Worker de queue démarré
- [ ] API accessible et fonctionnelle
- [ ] Token d'authentification obtenu
- [ ] Documentation API lue
- [ ] Tests effectués avec Postman/Insomnia

## 🔗 Liens Rapides

| Document | Description | Priorité |
|----------|-------------|----------|
| [FRONTEND_SETUP_GUIDE.md](./FRONTEND_SETUP_GUIDE.md) | Configuration backend | ⭐⭐⭐ |
| [API_NOTIFICATIONS.md](./API_NOTIFICATIONS.md) | Documentation API | ⭐⭐⭐ |
| [ENV_CONFIGURATION_REFERENCE.md](./ENV_CONFIGURATION_REFERENCE.md) | Variables `.env` | ⭐⭐ |
| [NOTIFICATIONS_MODULE.md](./NOTIFICATIONS_MODULE.md) | Documentation technique | ⭐ |

## 💡 Exemple Rapide

```javascript
// Créer une notification de rappel de paiement
const response = await fetch('http://localhost:8000/api/notifications', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
  },
  body: JSON.stringify({
    type: 'payment_reminder',
    recipient_email: 'parent@example.com',
    recipient_name: 'Marie Dupont',
    data: {
      student_name: 'Jean Dupont',
      amount: '500',
      due_date: '2025-02-01',
      tranche: '1',
    },
  }),
});

const result = await response.json();
console.log(result);
```

## 🆘 Besoin d'Aide ?

1. **Problème de configuration ?** → `FRONTEND_SETUP_GUIDE.md` (section Dépannage)
2. **Question sur l'API ?** → `API_NOTIFICATIONS.md`
3. **Erreur d'envoi ?** → Vérifiez que le worker de queue est démarré
4. **Email non reçu ?** → Vérifiez la configuration email dans `.env`

## 📞 Support

Contactez l'équipe backend pour (moi)  :
- Problèmes de configuration
- Questions techniques
- Bugs ou erreurs
- Demandes de nouvelles fonctionnalités

---

**Bon développement ! 🚀**

