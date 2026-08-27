# U-map - Carte Interactive du Campus UAC

Une application web moderne et interactive pour explorer le campus de l'Université d'Abomey-Calavi (UAC) au Bénin, avec système d'authentification complet et architecture cloud-native.

## 🌟 Fonctionnalités

### 🗺️ Carte Interactive
- Exploration du campus avec Leaflet.js
- Plus de 100 lieux référencés sur le campus
- Calcul d'itinéraires entre différents points
- Mode sombre/clair
- Interface responsive et moderne
- Animations fluides (AOS, SwiperJS)

### 🔐 Authentification Utilisateur
- **Connexion flexible** : E-mail ou nom d'utilisateur
- **Inscription** avec validation en temps réel
- **Réinitialisation mot de passe** par e-mail
- **Affichage/masquage mot de passe**
- **Détection Caps Lock**
- **Indicateur de force du mot de passe**
- **Vérification disponibilité username** en temps réel
- **Vérification disponibilité e-mail** en temps réel
- **Option "Se souvenir de moi"**
- **Messages d'erreur clairs** et spécifiques
- **Suggestions de mots de passe forts**
- **Validation instantanée des champs**

### 🎨 UI/UX Premium
- Design moderne avec TailwindCSS
- Animations fluides
- Interface responsive (mobile-first)
- Thème sombre/clair
- Composants réutilisables

### 🏛️ Administration
- Panel d'administration complet
- Gestion des utilisateurs
- Gestion des lieux
- Modération des signalements
- Statistiques et analytics

## 🛠️ Technologies Utilisées

### Frontend
- **Vue.js 3** (Composition API)
- **Vue Router 4** - Navigation
- **Pinia** - Gestion d'état
- **Leaflet.js** - Cartographie interactive
- **TailwindCSS** - Styling
- **AOS** - Animations au scroll
- **SwiperJS** - Carrousels d'images
- **Iconify** - Icônes

### Backend
- **Laravel 13** - Framework PHP
- **Laravel Sanctum** - Authentification API
- **MySQL/PostgreSQL** - Base de données
- **Redis** - Cache et queues
- **Laravel Queue** - Tâches asynchrones

### Infrastructure
- **Docker** - Conteneurisation
- **Docker Compose** - Développement local
- **Kubernetes** - Production
- **GitHub Actions** - CI/CD
- **Nginx** - Reverse proxy

## 📦 Installation

### Prérequis
- Node.js 18+
- PHP 8.3+
- Composer
- Docker (optionnel)
- PostgreSQL ou MySQL

### Installation Locale

```bash
# Cloner le dépôt
git clone https://github.com/VOTRE_USERNAME/U-map.git
cd U-map

# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# Frontend (nouveau terminal)
cd frontend
npm install
cp .env.example .env
npm run dev
```

### Installation avec Docker

```bash
# Cloner le dépôt
git clone https://github.com/VOTRE_USERNAME/U-map.git
cd U-map

# Copier les variables d'environnement
cp .env.production.example .env

# Lancer avec Docker Compose
docker-compose up -d

# Exécuter les migrations
docker-compose exec backend php artisan migrate --force
```

## 🚀 Déploiement en Production

### Option 1: Render + Vercel (Recommandé pour démarrer)

**Backend sur Render, Frontend sur Vercel**

```bash
# Suivez le guide complet
# Voir RENDER_VERCEL_DEPLOYMENT.md
```

**Avantages :**
- Déploiement automatique via GitHub
- Plans gratuits disponibles
- Simple à configurer
- SSL automatique

### Option 2: Docker Compose

```bash
# Configurer les variables
cp .env.production.example .env.production
# Éditer .env.production avec vos valeurs

# Déployer
chmod +x deploy.sh
./deploy.sh docker production
```

### Option 3: Kubernetes

```bash
# Configurer les secrets GitHub
# Ajouter les secrets dans Settings > Secrets and variables > Actions

# Déployer via CI/CD
git push origin main

# Ou manuellement
./deploy.sh kubernetes production
```

Pour plus de détails, consultez :
- [RENDER_VERCEL_DEPLOYMENT.md](RENDER_VERCEL_DEPLOYMENT.md) - Guide Render/Vercel
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Guide Docker/Kubernetes
- [PRODUCTION_README.md](PRODUCTION_README.md) - Guide rapide production

## 📂 Structure du Projet

```
U-map/
├── backend/              # API Laravel
│   ├── app/             # Application Laravel
│   ├── config/          # Configuration
│   ├── database/        # Migrations et seeders
│   ├── routes/          # Routes API
│   ├── Dockerfile       # Image Docker backend
│   └── .env.production  # Variables production
├── frontend/            # Application Vue.js
│   ├── src/
│   │   ├── components/  # Composants Vue
│   │   ├── pages/       # Pages (Login, Register, etc.)
│   │   ├── router/      # Configuration routeur
│   │   └── services/    # Services API
│   ├── Dockerfile       # Image Docker frontend
│   └── .env.production  # Variables production
├── k8s/                 # Manifests Kubernetes
│   ├── backend-deployment.yaml
│   ├── frontend-deployment.yaml
│   ├── services.yaml
│   ├── ingress.yaml
│   └── hpa.yaml
├── .github/             # GitHub Actions
│   └── workflows/
│       └── deploy.yml   # CI/CD
├── docker-compose.yml           # Développement
├── docker-compose.production.yml # Production
└── deploy.sh            # Script de déploiement
```

## 🎯 Catégories de Lieux

- 🏫 Enseignement
- 🏛️ Administratif
- 🎓 Facultés
- 🏠 Logements
- 🏥 Santé & Sécurité
- 🎭 Vie Étudiante
- ⚽ Sport
- 🚌 Mobilité
- 📡 Communication & Média
- 🏗️ Infrastructures Diverses

## � Sécurité

- Authentification avec Laravel Sanctum
- Protection contre brute force
- Rate limiting
- Validation des entrées
- CORS configuré
- Variables d'environnement sécurisées
- Health checks pour monitoring

Pour plus de détails, consultez [SECURITY_ARCHITECTURE.md](SECURITY_ARCHITECTURE.md).

## � Documentation

- [README.md](README.md) - Ce fichier
- [ARCHITECTURE.md](ARCHITECTURE.md) - Architecture cloud-native
- [SECURITY_ARCHITECTURE.md](SECURITY_ARCHITECTURE.md) - Sécurité détaillée
- [DEPLOYMENT.md](DEPLOYMENT.md) - Déploiement Kubernetes
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Guide de déploiement
- [RENDER_VERCEL_DEPLOYMENT.md](RENDER_VERCEL_DEPLOYMENT.md) - Guide Render/Vercel
- [PRODUCTION_README.md](PRODUCTION_README.md) - Guide rapide production

## 🧪 Tests

```bash
# Backend tests
cd backend
php artisan test

# Frontend tests (à implémenter)
cd frontend
npm run test
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Veuillez suivre ces étapes :

1. Fork le projet
2. Créez une branche (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 Licence

MIT License - Libre d'utilisation

## 👨‍💻 Auteur

**Edson Lawson**

---

Développé avec ❤️ pour la communauté UAC
