# U-Map Backend API

Backend Laravel API pour l'application U-Map - Carte Interactive du Campus UAC.

## 🌟 Fonctionnalités

### 🔐 Authentification
- **Connexion flexible** : E-mail ou nom d'utilisateur
- **Inscription** avec validation
- **Réinitialisation mot de passe** par e-mail
- **Laravel Sanctum** pour l'authentification API
- **Protection brute force** (5 tentatives, 15 min)
- **Option "Se souvenir de moi"** avec expiration configurable

### 🗺️ Carte & Lieux
- Gestion des lieux du campus
- Recherche de lieux
- Catégorisation des infrastructures
- Validation des coordonnées GPS

### 💬 Messagerie
- Messagerie éphémère (7 jours expiration)
- Nettoyage automatique des messages expirés
- Chiffrement AES-256 des messages
- Anti-spam protection

### 🏛️ Administration
- Panel d'administration complet
- Gestion des utilisateurs (RBAC)
- Modération des signalements
- Statistiques et analytics
- Audit logging

### 🔒 Sécurité
- RBAC avec 4 niveaux (User, Moderator, Admin, Super Admin)
- Audit logging complet
- Rate limiting avancé
- Input validation et sanitization
- Security headers (CSP, HSTS, X-Frame-Options)
- Protection XSS et CSRF

## 🛠️ Technologies

- **Laravel 13** (PHP 8.3)
- **Laravel Sanctum** - Authentification API
- **MySQL/PostgreSQL** - Base de données
- **Redis** - Cache et queues
- **Laravel Queue** - Tâches asynchrones

## 📦 Installation

```bash
# Installer les dépendances
composer install

# Copier l'environnement
cp .env.example .env

# Générer la clé
php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Démarrer le serveur
php artisan serve
```

## 🚀 Utilisation

### Développement

```bash
# Serveur de développement
php artisan serve

# Queue worker
php artisan queue:work

# Scheduler (pour le développement)
php artisan schedule:work
```

### Tests

```bash
# Exécuter les tests
php artisan test

# Avec coverage
php artisan test --coverage
```

## 📂 Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Contrôleurs API
│   │   ├── Middleware/      # Middleware (Auth, Rate Limit, etc.)
│   │   └── Requests/        # FormRequests validation
│   ├── Models/              # Modèles Eloquent
│   ├── Jobs/                # Queue jobs
│   └── Services/            # Services métier
├── config/                  # Configuration Laravel
├── database/
│   ├── migrations/          # Migrations DB
│   └── seeders/             # Seeders
├── routes/
│   ├── api.php              # Routes API
│   └── web.php              # Routes web
└── tests/                   # Tests
```

## 🔌 API Endpoints

### Authentification
- `POST /api/login` - Connexion utilisateur
- `POST /api/register` - Inscription utilisateur
- `POST /api/logout` - Déconnexion (authentifié)
- `POST /api/check-username` - Vérification username
- `POST /api/check-email` - Vérification e-mail
- `POST /api/forgot-password` - Demande reset mot de passe
- `POST /api/reset-password` - Reset mot de passe

### Lieux
- `GET /api/places` - Liste des lieux
- `GET /api/places/search` - Recherche lieux
- `POST /api/places` - Créer lieu (authentifié)

### Messagerie
- `GET /api/conversations` - Conversations utilisateur
- `GET /api/messages/{receiverId}` - Messages avec utilisateur
- `POST /api/messages` - Envoyer message (authentifié)

### Administration (Admin)
- `POST /api/admin/login` - Connexion admin
- `GET /api/admin/stats` - Statistiques
- `GET /api/admin/users` - Liste utilisateurs
- `PUT /api/admin/users/{id}/restrict` - Restreindre utilisateur
- `GET /api/admin/reports` - Signalements
- `PUT /api/admin/reports/{id}/resolve` - Résoudre signalement

## 🔐 Sécurité

Pour plus de détails sur la sécurité, consultez [SECURITY_ARCHITECTURE.md](../SECURITY_ARCHITECTURE.md).

## 📚 Documentation

- [README principal](../README.md)
- [Architecture](../ARCHITECTURE.md)
- [Déploiement](../DEPLOYMENT.md)
- [Sécurité](../SECURITY_ARCHITECTURE.md)

## 👨‍💻 Auteur

**Edson Lawson**

## 📄 Licence

MIT License

---

Développé avec ❤️ pour la communauté UAC
