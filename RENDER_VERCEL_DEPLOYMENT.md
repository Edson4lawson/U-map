# 🚀 Déploiement sur Render et Vercel

Ce guide explique comment déployer U-Map sur Render (backend) et Vercel (frontend).

## 📋 Architecture

- **Backend (Laravel)** : Hébergé sur Render
- **Frontend (Vue.js)** : Hébergé sur Vercel
- **Base de données** : PostgreSQL sur Render

## 🔧 Prérequis

- Compte Render (https://render.com)
- Compte Vercel (https://vercel.com)
- Compte GitHub (pour le déploiement automatique)

---

## 🌐 Déploiement Backend sur Render

### 1. Créer un compte Render

1. Allez sur https://render.com
2. Créez un compte avec GitHub
3. Autorisez Render à accéder à votre repository

### 2. Créer la base de données PostgreSQL

1. Dans Render, cliquez sur **New** → **PostgreSQL**
2. Configurez :
   - **Name** : umap-db
   - **Database** : umap_prod
   - **User** : umap
   - **Plan** : Free
3. Cliquez sur **Create Database**
4. Notez les informations de connexion (Internal Database URL)

### 3. Déployer le Backend

#### Option A : Via Blueprint (render.yaml)

1. Le fichier `render.yaml` est déjà configuré à la racine du projet
2. Dans Render, cliquez sur **New** → **Blueprint**
3. Connectez votre repository GitHub
4. Render détectera automatiquement le `render.yaml`
5. Cliquez sur **Apply**

#### Option B : Manuellement

1. Dans Render, cliquez sur **New** → **Web Service**
2. Connectez votre repository GitHub
3. Configurez :
   - **Name** : umap-backend
   - **Root Directory** : backend
   - **Runtime** : PHP
   - **Build Command** : `composer install --no-dev --optimize-autoloader && php artisan key:generate && php artisan migrate --force`
   - **Start Command** : `php artisan serve --host=0.0.0.0 --port=$PORT`
4. Cliquez sur **Advanced**
5. Ajoutez les variables d'environnement (voir ci-dessous)
6. Cliquez sur **Create Web Service**

### 4. Configurer les Variables d'Environnement

Dans les settings du service Render, ajoutez ces variables :

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-backend.onrender.com
APP_KEY=votre_app_key_généré_ou_gardé

# Base de données (utilisez l'URL interne de Render)
DB_CONNECTION=pgsql
DB_HOST=dpg-xxxxx.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=umap_prod
DB_USERNAME=umap
DB_PASSWORD=votre_password_render

# Cache et Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# CORS (mettez l'URL Vercel)
ALLOWED_ORIGINS=https://votre-frontend.vercel.app

# API Keys
GROQ_API_KEY=votre_clé_groq
GEMINI_API_KEY=votre_clé_gemini

# Mail (optionnel)
MAIL_MAILER=log
```

### 5. Configurer le Disk Storage

1. Dans les settings du service Render
2. Allez dans **Disks**
3. Cliquez sur **Add Disk**
4. Configurez :
   - **Name** : data
   - **Mount Path** : /var/www/html/storage
   - **Size** : 1 GB
5. Cliquez sur **Add Disk**

### 6. Vérifier le Déploiement

1. Attendez que le build se termine
2. Cliquez sur **Logs** pour vérifier
3. Testez l'endpoint health : `https://votre-backend.onrender.com/api/health`

---

## 🎨 Déploiement Frontend sur Vercel

### 1. Créer un compte Vercel

1. Allez sur https://vercel.com
2. Créez un compte avec GitHub
3. Autorisez Vercel à accéder à votre repository

### 2. Importer le Projet

1. Dans Vercel, cliquez sur **Add New** → **Project**
2. Sélectionnez votre repository GitHub
3. Configurez :
   - **Project Name** : umap-frontend
   - **Root Directory** : frontend
   - **Framework Preset** : Vite
4. Cliquez sur **Deploy**

### 3. Configurer les Variables d'Environnement

1. Dans les settings du projet Vercel
2. Allez dans **Environment Variables**
3. Ajoutez :
   - **Key** : `VITE_API_URL`
   - **Value** : `https://votre-backend.onrender.com/api`
   - **Environment** : Production, Preview, Development
4. Cliquez sur **Save**

### 4. Configurer le vercel.json

Le fichier `frontend/vercel.json` est déjà configuré avec :
- Build command
- Output directory
- Security headers
- Rewrites pour SPA

### 5. Redéployer après Configuration

1. Cliquez sur **Deployments**
2. Cliquez sur **Redeploy** sur le dernier déploiement
3. Attendez que le build se termine

### 6. Vérifier le Déploiement

1. Cliquez sur le domaine généré (ex: umap-frontend.vercel.app)
2. Vérifiez que l'application fonctionne
3. Testez la connexion au backend

---

## 🔗 Connecter Frontend et Backend

### 1. Mettre à jour ALLOWED_ORIGINS

Dans Render (backend) :
1. Allez dans les settings du service
2. Mettez à jour la variable `ALLOWED_ORIGINS` :
   ```
   ALLOWED_ORIGINS=https://votre-frontend.vercel.app
   ```
3. Redéployez le backend

### 2. Mettre à jour VITE_API_URL

Dans Vercel (frontend) :
1. Allez dans Environment Variables
2. Mettez à jour `VITE_API_URL` :
   ```
   VITE_API_URL=https://votre-backend.onrender.com/api
   ```
3. Redéployez le frontend

### 3. Tester la Connexion

1. Ouvrez le frontend dans le navigateur
2. Essayez de vous connecter
3. Vérifiez les logs Render et Vercel en cas d'erreur

---

## 🔄 Déploiement Automatique (CI/CD)

### Render

Render déploie automatiquement à chaque push sur la branche configurée (main par défaut).

### Vercel

Vercel déploie automatiquement à chaque push sur GitHub.

### Workflow Recommandé

```bash
# Faire des changements
git add .
git commit -m "Description des changements"
git push origin main

# Render et Vercel déploient automatiquement
```

---

## 📊 Monitoring

### Render

1. Allez sur votre service backend
2. **Metrics** : CPU, Memory, Response Time
3. **Logs** : Logs de l'application
4. **Events** : Événements de déploiement

### Vercel

1. Allez sur votre projet frontend
2. **Analytics** : Visites, performance
3. **Logs** : Logs de build et runtime
4. **Deployments** : Historique des déploiements

---

## 🔧 Dépannage

### Erreur CORS

**Symptôme** : Erreur CORS dans le navigateur

**Solution** :
1. Vérifiez `ALLOWED_ORIGINS` dans Render
2. Assurez-vous que l'URL frontend est correcte
3. Redéployez le backend

### Erreur de connexion DB

**Symptôme** : Erreur de connexion base de données

**Solution** :
1. Vérifiez les variables DB_* dans Render
2. Utilisez l'URL interne de Render pour DB_HOST
3. Vérifiez que la base de données est en cours d'exécution

### Build échoue sur Vercel

**Symptôme** : Build échoue sur Vercel

**Solution** :
1. Vérifiez les logs de build
2. Assurez-vous que `package.json` est correct
3. Vérifiez que les dépendances sont à jour

### Timeout sur Render

**Symptôme** : Timeout lors du déploiement

**Solution** :
1. Render Free plan a un timeout de 10 minutes
2. Optimisez le build (cache composer)
3. Passez au plan Starter si nécessaire

---

## 💰 Coûts

### Render (Free Plan)
- **Backend** : Gratuit (750 heures/mois)
- **PostgreSQL** : Gratuit (90 jours, puis $7/mois)
- **Storage** : 1 GB gratuit
- **Limitations** : Timeout 10 min, spin-up après inactivité

### Vercel (Hobby Plan)
- **Frontend** : Gratuit (100GB bandwidth/mois)
- **Limitations** : Build timeout 60s, pas de fonctions serveur

### Recommandation
- Commencez avec les plans gratuits
- Passez aux plans payants si nécessaire (Starter Render $7/mois, Pro Vercel $20/mois)

---

## 🚀 Mise à jour de l'Application

### Backend

```bash
git add .
git commit -m "Mise à jour backend"
git push origin main
# Render déploie automatiquement
```

### Frontend

```bash
git add .
git commit -m "Mise à jour frontend"
git push origin main
# Vercel déploie automatiquement
```

### Les deux simultanément

```bash
git add .
git commit -m "Mise à jour complète"
git push origin main
# Render et Vercel déploient automatiquement
```

---

## 📝 Checklist de Déploiement

### Backend (Render)
- [ ] Compte Render créé
- [ ] Base de données PostgreSQL créée
- [ ] Variables d'environnement configurées
- [ ] Disk storage configuré
- [ ] Backend déployé avec succès
- [ ] Health check fonctionnel
- [ ] Logs sans erreurs

### Frontend (Vercel)
- [ ] Compte Vercel créé
- [ ] Projet importé
- [ ] VITE_API_URL configuré
- [ ] Frontend déployé avec succès
- [ ] Connexion backend fonctionnelle
- [ ] Authentification testée

### Intégration
- [ ] ALLOWED_ORIGINS mis à jour
- [ ] CORS fonctionnel
- [ ] Test de bout en bout réussi
- [ ] Monitoring configuré

---

## 🆘 Support

### Render
- Documentation : https://render.com/docs
- Status : https://status.render.com
- Support : support@render.com

### Vercel
- Documentation : https://vercel.com/docs
- Status : https://status.vercel.com
- Support : support@vercel.com

---

**Version** : 1.0.0  
**Dernière mise à jour** : 2026-07-06
