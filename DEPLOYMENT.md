# 🚀 U-map - Guide de Déploiement Production Kubernetes

## 📋 Table des Matières

1. [Architecture](#architecture)
2. [Prérequis](#prérequis)
3. [Configuration](#configuration)
4. [Déploiement Local (Docker Compose)](#déploiement-local-docker-compose)
5. [Déploiement Production (Docker Compose)](#déploiement-production-docker-compose)
6. [Déploiement Kubernetes](#déploiement-kubernetes)
7. [Script de Déploiement Automatisé](#script-de-déploiement-automatisé)
8. [CI/CD GitHub Actions](#cicd-github-actions)
9. [Surveillance et Maintenance](#surveillance-et-maintenance)
10. [Backup et Recovery](#backup-et-recovery)
11. [Troubleshooting](#troubleshooting)

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Ingress (HTTPS)                      │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌───────────────────────┐       ┌───────────────────────┐
│   Frontend (Vue.js)   │       │   Backend (Laravel)   │
│   Nginx: 2 replicas   │       │   PHP-FPM: 3 replicas │
└───────────────────────┘       └───────────────────────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    ▼                 ▼                 ▼
           ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
           │   MySQL     │   │   Redis     │   │   Worker    │
           │ (External)  │   │   Cache     │   │   Queue     │
           └─────────────┘   └─────────────┘   └─────────────┘
                                                │
                                                ▼
                                         ┌─────────────┐
                                         │  Scheduler  │
                                         │   (Cron)    │
                                         └─────────────┘
```

---

## 🔧 Prérequis

### Pour Docker Compose (Développement)
- Docker 20.10+
- Docker Compose 2.0+
- 4GB RAM minimum
- 10GB disque

### Pour Kubernetes (Production)
- Kubernetes cluster 1.25+
- kubectl configuré
- Helm 3.0+ (optionnel)
- Ingress Controller (nginx-ingress)
- Cert Manager (pour SSL automatique)
- 8GB RAM minimum pour le cluster
- 20GB disque

---

## ⚙️ Configuration

### 1. Variables d'Environnement

Copiez le fichier `.env.example` et configurez-le:

```bash
cp .env.example .env
```

**Variables obligatoires:**
```bash
# Application
APP_NAME=U-map
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:your-generated-key

# Base de données
DB_CONNECTION=mysql
DB_HOST=mysql-service
DB_PORT=3306
DB_DATABASE=umap
DB_USERNAME=umap
DB_PASSWORD=secure-password

# Redis
REDIS_HOST=redis-service
REDIS_PORT=6379

# API Keys
GROQ_API_KEY=your-groq-key
GEMINI_API_KEY=your-gemini-key

# CORS
ALLOWED_ORIGINS=https://your-domain.com,https://www.your-domain.com
```

### 2. Générer APP_KEY

```bash
php artisan key:generate
```

---

## 🐳 Déploiement Local (Docker Compose)

### 1. Lancer les services

```bash
# Développement
docker-compose up -d

# Production (avec nginx)
docker-compose --profile production up -d
```

### 2. Exécuter les migrations

```bash
docker-compose exec backend php artisan migrate --force
```

### 3. Vérifier les services

```bash
docker-compose ps
docker-compose logs -f backend
```

### 4. Accéder à l'application

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000/api
- MySQL: localhost:3306
- Redis: localhost:6379

---

## 🐳 Déploiement Production (Docker Compose)

### 1. Configuration Production

```bash
# Copier le fichier d'environnement production
cp .env.production.example .env.production

# Éditer avec vos valeurs
nano .env.production
```

**Variables obligatoires:**
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:your-generated-key

# Base de données PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=u_map_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=your-redis-host
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password

# Mail (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@your-domain.com

# CORS
ALLOWED_ORIGINS=https://your-frontend-domain.com

# Frontend
VITE_API_URL=https://your-backend-domain.com/api
```

### 2. Déploiement avec Script Automatisé

```bash
# Rendre le script exécutable
chmod +x deploy.sh

# Déployer
./deploy.sh docker production
```

### 3. Déploiement Manuel

```bash
# Copier les variables
cp .env.production .env

# Construire et démarrer
docker-compose -f docker-compose.production.yml up -d --build

# Exécuter les migrations
docker-compose -f docker-compose.production.yml exec -T backend php artisan migrate --force

# Vider le cache
docker-compose -f docker-compose.production.yml exec -T backend php artisan cache:clear
docker-compose -f docker-compose.production.yml exec -T backend php artisan config:clear
docker-compose -f docker-compose.production.yml exec -T backend php artisan route:clear
docker-compose -f docker-compose.production.yml exec -T backend php artisan view:clear
```

### 4. Vérification

```bash
# Statut des conteneurs
docker-compose -f docker-compose.production.yml ps

# Logs
docker-compose -f docker-compose.production.yml logs -f backend
docker-compose -f docker-compose.production.yml logs -f worker

# Health check
curl http://localhost:8000/api/health
```

---

## ☸️ Déploiement Kubernetes

### 1. Préparer le cluster

```bash
# Créer le namespace
kubectl apply -f k8s/namespace.yaml

# Configurer les secrets
kubectl create secret generic umap-secrets \
  --from-literal=app-key="base64:your-key" \
  --from-literal=db-database="umap" \
  --from-literal=db-username="umap" \
  --from-literal=db-password="secure-password" \
  --from-literal=groq-api-key="your-key" \
  --from-literal=gemini-api-key="your-key" \
  -n umap
```

### 2. Déployer les composants

```bash
# Persistent Volumes
kubectl apply -f k8s/pvc.yaml

# ConfigMap
kubectl apply -f k8s/configmap.yaml

# Deployments
kubectl apply -f k8s/backend-deployment.yaml
kubectl apply -f k8s/frontend-deployment.yaml
kubectl apply -f k8s/worker-deployment.yaml
kubectl apply -f k8s/scheduler-deployment.yaml

# Services
kubectl apply -f k8s/services.yaml

# HPA
kubectl apply -f k8s/hpa.yaml

# Ingress
kubectl apply -f k8s/ingress.yaml
```

### 3. Vérifier le déploiement

```bash
# Statut des pods
kubectl get pods -n umap

# Logs
kubectl logs -f deployment/umap-backend -n umap
kubectl logs -f deployment/umap-frontend -n umap

# Services
kubectl get svc -n umap

# Ingress
kubectl get ingress -n umap
```

### 4. Configuration SSL (Cert Manager)

```bash
# Installer Cert Manager
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.13.0/cert-manager.yaml

# Créer ClusterIssuer
cat <<EOF | kubectl apply -f -
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: your-email@example.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
EOF
```

---

## 🤖 Script de Déploiement Automatisé

Le script `deploy.sh` automatise le déploiement pour Docker Compose et Kubernetes.

### Utilisation

```bash
# Rendre le script exécutable
chmod +x deploy.sh

# Déploiement Docker Compose
./deploy.sh docker production

# Déploiement Kubernetes
./deploy.sh kubernetes production
```

### Fonctionnalités

- **Vérification des prérequis** (Docker, kubectl)
- **Chargement des variables d'environnement**
- **Déploiement automatique** (build, start, migrations)
- **Health checks** automatiques
- **Cache clearing** Laravel
- **Logs et statuts** des conteneurs/pods

### Ce que fait le script

**Pour Docker Compose:**
1. Vérifie Docker et Docker Compose
2. Charge les variables depuis `.env.production`
3. Arrête les conteneurs existants
4. Construit et démarre les nouveaux conteneurs
5. Exécute les migrations
6. Vide le cache Laravel
7. Affiche le statut des conteneurs
8. Effectue un health check

**Pour Kubernetes:**
1. Vérifie kubectl
2. Crée le namespace
3. Crée les secrets Kubernetes
4. Applique le ConfigMap
5. Applique les PVC
6. Déploie tous les composants (backend, frontend, worker, scheduler)
7. Applique les services
8. Applique l'HPA
9. Applique l'ingress
10. Attend que les déploiements soient prêts
11. Affiche le statut des pods, services et ingress
12. Effectue un health check

---

## 🔄 CI/CD GitHub Actions

### Configuration des Secrets

Ajoutez ces secrets dans votre repository GitHub:

- `KUBE_CONFIG`: Configuration kubectl encodée en base64
- `APP_KEY`: Clé d'application Laravel
- `DB_DATABASE`: Nom de la base de données
- `DB_USERNAME`: Utilisateur MySQL
- `DB_PASSWORD`: Mot de passe MySQL
- `GROQ_API_KEY`: Clé API Groq
- `GEMINI_API_KEY`: Clé API Gemini

### Workflow de déploiement

Le workflow `.github/workflows/deploy.yml` s'exécute automatiquement sur:
- Push vers `main`
- Déclenchement manuel

**Étapes:**
1. Build et push des images Docker
2. Déploiement Kubernetes
3. Mise à jour des secrets
4. Rollout status check

---

## 📊 Surveillance et Maintenance

### Monitoring

```bash
# Resource usage
kubectl top pods -n umap
kubectl top nodes

# HPA status
kubectl get hpa -n umap

# Events
kubectl get events -n umap --sort-by='.lastTimestamp'
```

### Logs

```bash
# Backend logs
kubectl logs -f deployment/umap-backend -n umap

# Worker logs
kubectl logs -f deployment/umap-worker -n umap

# Scheduler logs
kubectl logs -f deployment/umap-scheduler -n umap
```

### Mises à jour

```bash
# Rolling update
kubectl set image deployment/umap-backend \
  backend=umap-backend:new-tag -n umap

# Rollback
kubectl rollout undo deployment/umap-backend -n umap

# Historique
kubectl rollout history deployment/umap-backend -n umap
```

---

## 💾 Backup et Recovery

### Backup MySQL

```bash
# Backup
kubectl exec -n umap mysql-pod -- mysqldump -u root -p umap > backup.sql

# Restore
kubectl exec -i -n umap mysql-pod -- mysql -u root -p umap < backup.sql
```

### Backup Redis

```bash
# Backup
kubectl exec -n umap redis-pod -- redis-cli SAVE
kubectl cp umap/redis-pod:/data/dump.rdb ./redis-backup.rdb

# Restore
kubectl cp ./redis-backup.rdb umap/redis-pod:/data/dump.rdb
kubectl exec -n umap redis-pod -- redis-cli --rdb /data/dump.rdb
```

### Backup Storage

```bash
# Backup PVC
kubectl get pvc umap-backend-storage -n umap -o yaml > pvc-backup.yaml
```

---

## 🔧 Troubleshooting

### Pods ne démarrent pas

```bash
# Vérifier les events
kubectl describe pod <pod-name> -n umap

# Vérifier les logs
kubectl logs <pod-name> -n umap

# Vérifier les resources
kubectl describe node <node-name>
```

### Erreur de connexion DB

```bash
# Vérifier le service MySQL
kubectl get svc mysql-service -n umap

# Tester la connexion
kubectl run -it --rm debug --image=mysql:8.0 --restart=Never -- \
  mysql -h mysql-service -u umap -p
```

### Messages cleanup ne fonctionne pas

```bash
# Vérifier le scheduler
kubectl logs deployment/umap-scheduler -n umap

# Exécuter manuellement
kubectl exec deployment/umap-scheduler -n umap -- php artisan messages:cleanup
```

### Performance lente

```bash
# Vérifier les replicas HPA
kubectl get hpa -n umap

# Augmenter manuellement
kubectl scale deployment umap-backend --replicas=5 -n umap

# Vérifier Redis
kubectl exec -it redis-pod -n umap -- redis-cli INFO
```

---

## 📝 Checklist de Déploiement

- [ ] Variables d'environnement configurées
- [ ] APP_KEY généré
- [ ] Secrets Kubernetes créés
- [ ] Base de données MySQL externe configurée
- [ ] Redis déployé
- [ ] Cert Manager installé (SSL)
- [ ] Ingress configuré
- [ ] HPA activé
- [ ] Monitoring configuré
- [ ] Backup strategy en place
- [ ] CI/CD GitHub Actions configuré
- [ ] Tests manuels effectués
- [ ] Documentation mise à jour

---

## 🆘 Support

Pour toute question ou problème:
- Vérifier les logs Kubernetes
- Consulter la documentation Laravel
- Vérifier les status des services
- Contactez l'équipe DevOps

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2026-06-26
