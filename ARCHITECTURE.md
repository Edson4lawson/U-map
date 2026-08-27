# 🏗️ Architecture U-map - Vue d'Ensemble

## 📊 Architecture Cloud-Native Complète

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           EXTERNAL SERVICES                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                 │
│  │   MySQL DB   │  │    Redis     │  │   Cert Mgr   │                 │
│  │  (Managed)   │  │   (Cache)    │  │   (SSL/TLS)  │                 │
│  └──────────────┘  └──────────────┘  └──────────────┘                 │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
┌─────────────────────────────────────────────────────────────────────────┐
│                        KUBERNETES CLUSTER                                │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    INGRESS CONTROLLER (NGINX)                    │   │
│  │                    HTTPS + SSL Termination                       │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│  ┌──────────────┬──────────────┬──────────────┬──────────────┐        │
│  │              │              │              │              │        │
│  ▼              ▼              ▼              ▼              │        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │        │
│  │ Frontend │  │ Backend  │  │  Worker  │  │Scheduler │        │        │
│  │  Vue.js  │  │ Laravel  │  │  Queue   │  │   Cron   │        │        │
│  │  Nginx   │  │ PHP-FPM  │  │  Redis   │  │  Laravel  │        │        │
│  │  HPA:2-8 │  │ HPA:3-10 │  │ HPA:2-5  │  │   1 pod  │        │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │        │
│       │              │              │              │              │        │
│       └──────────────┴──────────────┴──────────────┘              │        │
│                              │                                       │        │
│  ┌─────────────────────────────────────────────────────────────┐   │        │
│  │              PERSISTENT VOLUME CLAIM (10GB)                   │   │        │
│  │              /var/www/html/storage                           │   │        │
│  └─────────────────────────────────────────────────────────────┘   │        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 🎯 Composants Principaux

### 1. Frontend (Vue.js + Nginx)
- **Framework**: Vue 3 (Composition API)
- **Build Tool**: Vite
- **Server**: Nginx Alpine
- **Replicas**: 2-8 (HPA)
- **Resources**: 64-128MB RAM, 100-200m CPU
- **Features**: PWA, Code splitting, Lazy loading

### 2. Backend (Laravel API)
- **Framework**: Laravel 13 (PHP 8.3)
- **Server**: PHP-FPM Alpine
- **Replicas**: 3-10 (HPA)
- **Resources**: 256-512MB RAM, 250-500m CPU
- **Features**: Sanctum Auth, Redis Cache, Queue System, User Authentication, Password Reset

### 3. Worker Queue
- **Purpose**: Traitement asynchrone des jobs
- **Queue**: Redis
- **Replicas**: 2-5 (HPA)
- **Resources**: 128-256MB RAM, 100-300m CPU
- **Jobs**: Messages cleanup, Notifications, Email

### 4. Scheduler
- **Purpose**: Tâches planifiées Laravel
- **Replicas**: 1 (single instance)
- **Resources**: 128-256MB RAM, 100-200m CPU
- **Tasks**: Messages cleanup (7 jours), Cache clear

## 🔐 Sécurité

### Application Level
- **CORS**: Origines autorisées configurées
- **Rate Limiting**: 60 req/min par utilisateur
- **Input Validation**: Sanitisation XSS
- **Security Headers**: CSP, HSTS, X-Frame-Options
- **API Authentication**: Laravel Sanctum

### Infrastructure Level
- **Secrets Management**: Kubernetes Secrets
- **TLS/SSL**: Cert Manager + Let's Encrypt
- **Network Policies**: Namespace isolation
- **Pod Security**: Non-root containers
- **RBAC**: Role-based access control

## ⚡ Performance & Scalabilité

### Horizontal Pod Autoscaling (HPA)
- **Backend**: Scale 3-10 replicas (CPU 70%, Memory 80%)
- **Frontend**: Scale 2-8 replicas (CPU 70%, Memory 80%)
- **Worker**: Scale 2-5 replicas (CPU 70%, Memory 80%)

### Caching Strategy
- **Redis**: Cache application, sessions, queue
- **Nginx**: Cache static assets (1 year)
- **Browser**: PWA caching (Service Worker)

### Database Optimization
- **Indexes**: Composites pour messages, users, places
- **Query Optimization**: Eager loading, pagination
- **Connection Pooling**: Laravel default

## 🔄 CI/CD Pipeline

### GitHub Actions Workflow
1. **Build**: Docker images multi-stage
2. **Push**: GitHub Container Registry
3. **Deploy**: Kubernetes rollout
4. **Verify**: Health checks
5. **Notify**: Status updates

### Deployment Strategy
- **Rolling Updates**: Zero downtime
- **Health Checks**: Liveness + Readiness probes
- **Rollback**: Automatic on failure
- **Canary**: Optional (future enhancement)

## 📊 Monitoring & Observability

### Health Checks
- **Backend**: `/api/health` endpoint
- **Frontend**: HTTP 200 on root
- **Worker**: Queue processing status
- **Scheduler**: Cron execution logs

### Logging
- **Application**: Laravel logs (storage/logs)
- **Infrastructure**: Kubernetes logs
- **Aggregation**: Centralized logging (future)

### Metrics
- **Resource Usage**: CPU, Memory, Disk
- **Application**: Response time, Error rate
- **Business**: Active users, Messages sent

## 💾 Data Management

### Storage
- **PVC**: 10GB for backend storage
- **Type**: ReadWriteOnce
- **Mount**: /var/www/html/storage

### Backup Strategy
- **MySQL**: External managed DB (automated backups)
- **Redis**: Snapshot persistence
- **Storage**: PVC backup (weekly)

### Disaster Recovery
- **RTO**: 1 hour
- **RPO**: 15 minutes
- **Strategy**: Multi-region (future)

## 🚀 Messagerie Éphémère

### Architecture
- **Expiration**: 7 jours automatique
- **Cleanup**: Job queue + Scheduler
- **Optimization**: Chunk deletion (1000 messages)
- **Performance**: Indexes composites

### Implementation
- **Job**: `CleanupExpiredMessages`
- **Command**: `php artisan messages:cleanup`
- **Schedule**: Daily via Laravel Scheduler
- **Queue**: Redis (messages-cleanup)

## 🌐 Network Architecture

### Services
- **ClusterIP**: Backend (9000), Frontend (80)
- **External**: MySQL, Redis (managed)
- **Ingress**: HTTPS termination

### DNS
- **Frontend**: `your-domain.com`
- **API**: `your-domain.com/api`
- **Internal**: Kubernetes DNS

## 🔧 Configuration Management

### ConfigMap
- Application URLs
- Database hosts/ports
- Redis configuration
- Allowed origins

### Secrets
- APP_KEY
- Database credentials
- API keys (Groq, Gemini)
- Sensitive configuration

## 📈 Scalability Planning

### Current Capacity
- **Users**: 10,000 concurrent
- **Messages**: 100,000/day
- **Storage**: 10GB
- **Bandwidth**: 1TB/month

### Future Enhancements
- **WebSockets**: Real-time chat
- **CDN**: Global asset distribution
- **Read Replicas**: MySQL scaling
- **Microservices**: Service decomposition

---

**Architecture Version**: 1.0.0  
**Status**: Production Ready  
**Last Updated**: 2026-06-26
