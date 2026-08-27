# 🚀 Guide de Déploiement U-Map

Ce guide explique comment déployer l'application U-Map en production.

## 📋 Prérequis

### Pour Docker Compose
- Docker 20.10+
- Docker Compose 2.0+

### Pour Kubernetes
- kubectl configuré
- Cluster Kubernetes (AWS EKS, GKE, ou autre)
- Accès au cluster

### Pour Render + Vercel
- Compte Render (backend)
- Compte Vercel (frontend)
- Compte GitHub (intégration CI/CD)

## 🔧 Configuration

### 1. Variables d'Environnement

Copiez le fichier d'exemple et configurez-le :

```bash
cp backend/.env.example backend/.env.production
```

Éditez `.env.production` avec vos valeurs réelles :

**Obligatoire :**
- `APP_KEY` : Générez avec `php artisan key:generate`
- `DB_*` : Configuration base de données PostgreSQL
- `REDIS_PASSWORD` : Mot de passe Redis
- `ALLOWED_ORIGINS` : Domaine frontend autorisé
- `VITE_API_URL` : URL backend pour le frontend

**Nouveau - Laravel Reverb (WebSockets) :**
- `BROADCAST_CONNECTION=reverb`
- `REVERB_APP_ID` : ID application Reverb
- `REVERB_APP_KEY` : Clé application Reverb
- `REVERB_APP_SECRET` : Secret application Reverb
- `REVERB_HOST` : Host Reverb (localhost en dev, domaine Render en prod)
- `REVERB_PORT` : Port Reverb (8080)
- `REVERB_SCHEME` : Scheme (http ou https)
- `VITE_REVERB_APP_KEY` : Même valeur que REVERB_APP_KEY
- `VITE_REVERB_HOST` : Host Reverb pour frontend
- `VITE_REVERB_PORT` : Port Reverb pour frontend
- `VITE_REVERB_SCHEME` : Scheme Reverb pour frontend

**Recommandé :**
- `MAIL_*` : Configuration SMTP pour les e-mails
- `AWS_*` : Configuration S3 pour le stockage
- `GROQ_API_KEY` : Clé API Groq pour IA
- `GEMINI_API_KEY` : Clé API Gemini pour IA

### 2. Générer APP_KEY

```bash
cd backend
php artisan key:generate
```

Copiez la valeur générée dans votre `.env.production`

## 🌐 Déploiement Render + Vercel

### Backend sur Render

#### 1. Nouvelles dépendances Composer

Les dépendances suivantes ont été ajoutées à `composer.json` :
- `laravel/reverb` : Serveur WebSocket Laravel
- `pusher/pusher-php-server` : Broadcasting

Render installera automatiquement ces dépendances lors du build.

#### 2. Variables d'environnement Render

Ajoutez ces variables dans votre dashboard Render :

**Variables existantes (vérifiez qu'elles sont toujours là) :**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` : Votre clé sécurisée
- `DB_CONNECTION=pgsql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- `GROQ_API_KEY` : Clé API Groq (optionnel)
- `GEMINI_API_KEY` : Clé API Gemini (optionnel)

**Nouvelles variables pour Laravel Reverb :**
```bash
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=umap-reverb
REVERB_APP_KEY=votre_clé_secrète
REVERB_APP_SECRET=votre_secret_encore_plus_secret
REVERB_HOST=votre-app-backend.onrender.com
REVERB_PORT=443
REVERB_SCHEME=https
```

#### 3. Configuration Build Render

Dans votre dashboard Render, assurez-vous que :

**Build Command :**
```bash
cd backend && composer install --no-dev --optimize-autoloader && php artisan key:generate && php artisan migrate --force && php artisan cache:clear
```

**Start Command :**
```bash
cd backend && php artisan reverb:start --host=0.0.0.0 --port=8080 &
php artisan serve --host=0.0.0.0 --port=8000
```

**Note :** Reverb doit démarrer sur le port 8080 et Laravel sur le port 8000.

#### 4. Nouveaux fichiers de configuration

Les fichiers suivants ont été créés/modifiés :
- `backend/config/broadcasting.php` : Configuration broadcasting
- `backend/config/reverb.php` : Configuration Reverb
- `backend/routes/channels.php` : Définition canaux privés
- `backend/app/Broadcasting/ChatChannel.php` : Canal chat
- `backend/app/Events/MessageSent.php` : Event broadcasting

Ces fichiers sont inclus dans le repository et seront déployés automatiquement.

#### 5. Migration de base de données

Une nouvelle migration a été ajoutée :
- `2026_07_08_000005_optimize_messages_table_for_chat.php`

Render exécutera automatiquement cette migration via le build command.

### Frontend sur Vercel

#### 1. Nouvelles dépendances npm

Les dépendances suivantes ont été ajoutées à `package.json` :
- `laravel-echo` : Client WebSocket Laravel
- `pusher-js` : Bibliothèque WebSocket

Vercel installera automatiquement ces dépendances.

#### 2. Variables d'environnement Vercel

Ajoutez ces variables dans votre dashboard Vercel (Settings > Environment Variables) :

**Variables existantes (vérifiez qu'elles sont toujours là) :**
- `VITE_API_URL=https://votre-app-backend.onrender.com/api`

**Nouvelles variables pour Laravel Reverb :**
```bash
VITE_REVERB_APP_KEY=votre_clé_secrète (même que Render)
VITE_REVERB_HOST=votre-app-backend.onrender.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

#### 3. Nouveaux fichiers

Les fichiers suivants ont été créés :
- `frontend/src/services/echo.js` : Service Echo WebSocket
- `frontend/src/pages/NotFound.vue` : Page 404

Ces fichiers sont inclus dans le repository et seront déployés automatiquement.

#### 4. Configuration Build Vercel

Vercel utilise la configuration par défaut de Vite :
- **Build Command :** `npm run build`
- **Output Directory :** `dist`

Aucune modification nécessaire.

### Étapes de déploiement

#### 1. Mettre à jour Render

```bash
# Les modifications sont déjà pushées sur GitHub
# Render se déploiera automatiquement depuis main
```

**Actions manuelles sur Render :**
1. Allez dans votre dashboard Render
2. Ajoutez les nouvelles variables d'environnement Reverb
3. Redémarrez le service manuellement si nécessaire

#### 2. Mettre à jour Vercel

```bash
# Les modifications sont déjà pushées sur GitHub
# Vercel se déploiera automatiquement depuis main
```

**Actions manuelles sur Vercel :**
1. Allez dans votre dashboard Vercel
2. Ajoutez les nouvelles variables d'environnement Reverb
3. Redémarrez le déploiement si nécessaire

#### 3. Vérifier le déploiement

**Tester les WebSockets :**
```javascript
// Dans la console du navigateur sur votre site Vercel
// Vérifiez que Echo se connecte correctement
```

**Tester les messages en temps réel :**
1. Ouvrez le chat sur deux navigateurs différents
2. Envoyez un message
3. Vérifiez qu'il apparaît en temps réel sur les deux

### Checklist Post-Déploiement

**Render (Backend) :**
- [ ] Variables d'environnement Reverb ajoutées
- [ ] Service redémarré
- [ ] Reverb fonctionne sur le port 8080
- [ ] Broadcasting routes actives (`/broadcasting/auth`)
- [ ] Migration messages table exécutée

**Vercel (Frontend) :**
- [ ] Variables d'environnement Reverb ajoutées
- [ ] Build réussi
- [ ] Echo service se connecte
- [ ] Messages en temps réel fonctionnent

**Tests fonctionnels :**
- [ ] Chat en temps réel fonctionne
- [ ] Notifications toast apparaissent
- [ ] Liens lieux dans chat fonctionnent
- [ ] Filtres lieux fonctionnent
- [ ] Page 404 s'affiche correctement
- [ ] Ajout de lieux fonctionne (distance 10km)

## 🐳 Déploiement Docker Compose

### Déploiement

```bash
# Rendre le script exécutable (Linux/Mac)
chmod +x deploy.sh

# Déployer avec Docker Compose
./deploy.sh docker production
```

Ou manuellement :

```bash
# Copier les variables d'environnement
cp .env.production .env

# Construire et démarrer
docker-compose -f docker-compose.production.yml up -d --build

# Exécuter les migrations
docker-compose -f docker-compose.production.yml exec -T backend php artisan migrate --force

# Vider le cache
docker-compose -f docker-compose.production.yml exec -T backend php artisan cache:clear
```

### Vérifier le déploiement

```bash
# Voir les conteneurs
docker-compose -f docker-compose.production.yml ps

# Voir les logs
docker-compose -f docker-compose.production.yml logs -f

# Health check
curl http://localhost:8000/api/health
```

## ☸️ Déploiement Kubernetes

### Configuration GitHub Secrets

Ajoutez ces secrets dans votre repository GitHub :

- `KUBE_CONFIG` : Configuration kubectl encodée en base64
- `APP_KEY` : Clé application Laravel
- `DB_DATABASE` : Nom base de données
- `DB_USERNAME` : Utilisateur base de données
- `DB_PASSWORD` : Mot de passe base de données
- `GROQ_API_KEY` : Clé API Groq
- `GEMINI_API_KEY` : Clé API Gemini

### Déploiement Automatique (CI/CD)

Le workflow GitHub Actions se déclenche automatiquement sur push vers `main` :

```bash
git add .
git commit -m "Deploy to production"
git push origin main
```

### Déploiement Manuel

```bash
# Déployer avec Kubernetes
./deploy.sh kubernetes production
```

Ou manuellement :

```bash
# Appliquer les manifests
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/pvc.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/backend-deployment.yaml
kubectl apply -f k8s/frontend-deployment.yaml
kubectl apply -f k8s/worker-deployment.yaml
kubectl apply -f k8s/scheduler-deployment.yaml
kubectl apply -f k8s/services.yaml
kubectl apply -f k8s/hpa.yaml
kubectl apply -f k8s/ingress.yaml

# Attendre que les déploiements soient prêts
kubectl rollout status deployment/umap-backend -n umap
kubectl rollout status deployment/umap-frontend -n umap
```

### Vérifier le déploiement

```bash
# Voir les pods
kubectl get pods -n umap

# Voir les services
kubectl get services -n umap

# Voir l'ingress
kubectl get ingress -n umap

# Logs
kubectl logs -f deployment/umap-backend -n umap
```

## 🔍 Health Checks

### Backend API

```bash
curl https://your-backend.com/api/health
```

Réponse attendue :
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "version": "1.0.0",
  "environment": "production"
}
```

### Frontend

```bash
curl https://your-frontend.com/
```

## 📊 Monitoring

### Logs Docker

```bash
docker-compose -f docker-compose.production.yml logs -f backend
docker-compose -f docker-compose.production.yml logs -f worker
```

### Logs Kubernetes

```bash
kubectl logs -f deployment/umap-backend -n umap
kubectl logs -f deployment/umap-worker -n umap
```

### Métriques

Les health checks sont configurés dans :
- Docker Compose : healthcheck dans chaque service
- Kubernetes : livenessProbe et readinessProbe dans les deployments

## 🔒 Sécurité

### Avant le déploiement

1. **Changer tous les mots de passe par défaut**
2. **Générer un APP_KEY sécurisé**
3. **Configurer HTTPS/SSL**
4. **Restreindre l'accès à la base de données**
5. **Activer le firewall**

### En production

1. **Désactiver APP_DEBUG** (déjà fait dans .env.production)
2. **Utiliser HTTPS uniquement**
3. **Configurer les backups automatiques**
4. **Surveiller les logs**
5. **Mettre à jour régulièrement**

## 🔄 Mises à jour

### Docker Compose

```bash
# Puller les dernières modifications
git pull origin main

# Reconstruire et redémarrer
docker-compose -f docker-compose.production.yml up -d --build

# Exécuter les migrations
docker-compose -f docker-compose.production.yml exec -T backend php artisan migrate --force
```

### Kubernetes

```bash
# Puller les dernières modifications
git pull origin main

# Le CI/CD se déclenche automatiquement
# Ou manuellement :
kubectl apply -f k8s/
kubectl rollout restart deployment/umap-backend -n umap
kubectl rollout restart deployment/umap-frontend -n umap
```

## 🐛 Dépannage

### Conteneurs qui ne démarrent pas

```bash
# Docker
docker-compose -f docker-compose.production.yml logs backend

# Kubernetes
kubectl describe pod <pod-name> -n umap
kubectl logs <pod-name> -n umap
```

### Erreur de connexion base de données

Vérifiez que :
- Les variables DB_* sont correctes
- La base de données est accessible
- Les migrations ont été exécutées

### Erreur CORS

Vérifiez que :
- `ALLOWED_ORIGINS` contient votre domaine frontend
- Le frontend utilise la bonne URL API

## 📞 Support

En cas de problème :
1. Vérifiez les logs
2. Consultez le health check
3. Vérifiez les variables d'environnement
4. Consultez la documentation Kubernetes/Docker

## 🎯 Checklist Pré-Déploiement

- [ ] `.env.production` configuré avec toutes les variables
- [ ] APP_KEY généré et sécurisé
- [ ] Base de données PostgreSQL configurée
- [ ] Redis configuré
- [ ] Service SMTP configuré (pour les e-mails)
- [ ] S3 configuré (pour le stockage)
- [ ] Domaines configurés dans ALLOWED_ORIGINS
- [ ] VITE_API_URL configuré
- [ ] Variables Reverb configurées (Render + Vercel)
- [ ] HTTPS/SSL configuré
- [ ] Backups automatiques configurés
- [ ] Monitoring configuré
- [ ] Firewall activé

## 📝 Résumé des modifications pour Render + Vercel

### Ce qui a changé dans le code

**Backend (Render) :**
1. **Nouvelles dépendances Composer :**
   - `laravel/reverb` (WebSockets)
   - `pusher/pusher-php-server` (Broadcasting)

2. **Nouveaux fichiers :**
   - `config/broadcasting.php` (Configuration broadcasting)
   - `config/reverb.php` (Configuration Reverb)
   - `routes/channels.php` (Canaux privés)
   - `app/Broadcasting/ChatChannel.php` (Canal chat)
   - `app/Events/MessageSent.php` (Event broadcasting)
   - `database/migrations/2026_07_08_000005_optimize_messages_table_for_chat.php`

3. **Fichiers modifiés :**
   - `bootstrap/app.php` (Channels activés)
   - `routes/api.php` (Broadcast::routes ajouté)
   - `app/Http/Controllers/AiController.php` (Format [LIEU:Nom|ID])

**Frontend (Vercel) :**
1. **Nouvelles dépendances npm :**
   - `laravel-echo` (Client WebSocket)
   - `pusher-js` (Bibliothèque WebSocket)

2. **Nouveaux fichiers :**
   - `src/services/echo.js` (Service Echo)
   - `src/pages/NotFound.vue` (Page 404)

3. **Fichiers modifiés :**
   - `src/App.vue` (Echo subscriptions, design erreur)
   - `src/pages/Chat.vue` (Echo chat, liens lieux)
   - `src/pages/Home.vue` (Filtres lieux)
   - `src/pages/Lieux.vue` (Filtres, distance 10km)
   - `src/pages/Visites.vue` (Lisibilité badges)
   - `src/pages/Login.vue` & `Register.vue` (Icônes)
   - `src/router/index.js` (Route 404)
   - `src/stores/badges.js` (Icônes)

### Actions à effectuer sur Render

1. **Ajouter les variables d'environnement Reverb :**
   ```
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=umap-reverb
   REVERB_APP_KEY=votre_clé_secrète
   REVERB_APP_SECRET=votre_secret_encore_plus_secret
   REVERB_HOST=votre-app-backend.onrender.com
   REVERB_PORT=443
   REVERB_SCHEME=https
   ```

2. **Modifier le Start Command :**
   ```
   cd backend && php artisan reverb:start --host=0.0.0.0 --port=8080 &
   php artisan serve --host=0.0.0.0 --port=8000
   ```

3. **Redémarrer le service** pour appliquer les changements

4. **Vérifier les logs** pour confirmer que Reverb démarre correctement

### Actions à effectuer sur Vercel

1. **Ajouter les variables d'environnement Reverb :**
   ```
   VITE_REVERB_APP_KEY=votre_clé_secrète (même que Render)
   VITE_REVERB_HOST=votre-app-backend.onrender.com
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   ```

2. **Redéployer** (automatique depuis GitHub ou manuel depuis dashboard)

3. **Vérifier le build** dans le dashboard Vercel

### Tests à effectuer après déploiement

1. **Chat en temps réel :** Ouvrir le chat sur 2 navigateurs, tester l'envoi de messages
2. **Notifications :** Vérifier que les toast notifications apparaissent
3. **Liens lieux :** Tester les liens cliquables dans les réponses IA
4. **Filtres lieux :** Tester les boutons Études, Resto, Favoris depuis Home
5. **Page 404 :** Accéder à une URL inexistante
6. **Ajout lieux :** Tester l'ajout de lieux sur le campus (distance 10km)
