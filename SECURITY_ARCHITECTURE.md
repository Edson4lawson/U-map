# 🔐 U-map Security Architecture - Production-Grade Hardening

## 📋 Executive Summary

This document provides a comprehensive overview of the security architecture implemented for U-map, transforming it into a production-grade, cloud-native application with enterprise-level security measures.

**Security Level**: Production-Grade  
**Compliance**: Zero Trust Model  
**Architecture**: Cloud-Native Kubernetes  
**Encryption**: AES-256 at rest and in transit

---

## 🛡️ 1. Backend Security (Laravel)

### 1.1 RBAC System (Role-Based Access Control)

**Implementation**: Complete hierarchical role system with 4 levels
- **User**: Basic access, messaging, place creation
- **Moderator**: Content moderation, user restriction
- **Admin**: User management, full content control
- **Super Admin**: System configuration, impersonation

**Components**:
- `UserRole` enum with hierarchy validation
- `CheckRole` middleware for route protection
- Laravel Gates for fine-grained permissions
- Policies for all entities (Message, Place, User, Report, Event)

**Zero Trust Enforcement**:
- Every action requires explicit authorization
- Ownership verification on all resources
- Role hierarchy validation prevents privilege escalation

### 1.1.1 User Authentication System

**Implementation**: Complete authentication system with Laravel Sanctum
- **Flexible Login**: Email or username authentication
- **Registration**: Real-time validation (username/email availability)
- **Password Reset**: Email-based reset with secure tokens
- **Remember Me**: Configurable token expiration (1 month)
- **Security Features**:
  - Brute force protection (5 attempts, 15 min lockout)
  - Password strength validation
  - Secure token storage
  - Rate limiting on auth endpoints

**Endpoints**:
- `POST /api/login` - User authentication
- `POST /api/register` - User registration
- `POST /api/logout` - User logout (authenticated)
- `POST /api/check-username` - Username availability
- `POST /api/check-email` - Email availability
- `POST /api/forgot-password` - Password reset request
- `POST /api/reset-password` - Password reset execution

**Security Measures**:
- Email enumeration prevention (always return success on forgot-password)
- Secure password hashing (bcrypt with 12 rounds)
- Token expiration management
- Session encryption enabled

### 1.2 Audit Logging System

**Table**: `audit_logs` with comprehensive tracking
- User ID, action, resource type/ID
- IP address, user agent
- Sanitized payload (no sensitive data)
- Status (success/failure/blocked)
- Timestamp with indexes for performance

**Middleware**: Automatic logging of all authenticated requests
- Logs sensitive operations (create, update, delete)
- Sanitizes sensitive data before logging
- Tracks blocked attempts and failures

**Service**: `AuditLogService` for programmatic logging
- Login/logout events
- Failed authentication attempts
- Security events (rate limit exceeded, spam detected)

### 1.3 Input Security Hardening

**FormRequests** with strict validation:
- `SendMessageRequest`: Content sanitization, XSS prevention
- `StorePlaceRequest`: Coordinate validation, URL sanitization
- `UpdateUserRequest`: Role hierarchy validation
- `StoreReportRequest`: Evidence URL validation

**Sanitization**:
- HTML tag removal (`strip_tags`)
- Control character removal
- Whitespace normalization
- Regex pattern validation

### 1.4 Message Encryption (AES-256)

**Service**: `MessageEncryptionService`
- Laravel Crypt (AES-256-CBC by default)
- Automatic encryption on save
- Automatic decryption on access
- Error handling with logging

**Model**: `Message` with encryption attributes
- `encrypted_content` column (hidden from JSON)
- `is_encrypted` flag
- `decrypted_content` accessor
- Automatic encryption in `setContentAttribute`

**Migration**: Encrypts existing messages in chunks
- Processes 100 messages at a time
- Logs encryption progress
- Handles errors gracefully

### 1.5 Secure Message Cleanup

**Job**: `CleanupExpiredMessages` with security enhancements
- Chunked deletion (1000 messages)
- Encryption verification before deletion
- Audit logging of all deletions
- No message content in logs
- Error handling with retry logic

**Anti-Data-Leak Rules**:
- Never log message content
- Never cache unencrypted messages
- Debug output disabled in production
- Immediate deletion from database

---

## 🔒 2. API Security Middleware Stack

### 2.1 Advanced Rate Limiting

**Middleware**: `AdvancedRateLimit`
- User-based rate limiting (default: 60 req/min)
- IP-based rate limiting (2x user limit)
- Per-endpoint tracking
- Exponential backoff on violations
- Audit logging of blocked attempts

**Headers**:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

### 2.2 Anti-Spam Protection

**Middleware**: `AntiSpam`
- Spam score tracking (user + IP)
- Rapid message sending detection (10 msg/min)
- Suspicious pattern detection
- User agent validation
- Endpoint switching detection

**Triggers**:
- >50 different endpoints in 5 minutes
- Missing user agent
- Suspicious user agent patterns
- Automated tool detection

### 2.3 Brute Force Protection

**Middleware**: `BruteForceProtection`
- IP-based lockout (5 attempts, 15 min)
- Email-based lockout (5 attempts, 15 min)
- Attempt tracking with expiration
- Automatic unlock on success
- Audit logging of all failures

**Configuration**:
- Configurable max attempts
- Configurable decay period
- Separate IP and email tracking

### 2.4 Security Headers

**Middleware**: `SecurityHeaders`
- Content Security Policy (CSP)
- HTTP Strict Transport Security (HSTS)
- X-Frame-Options (DENY)
- X-Content-Type-Options (nosniff)
- X-XSS-Protection
- Referrer-Policy
- Permissions-Policy

---

## ☸️ 3. Kubernetes Security Hardening

### 3.1 Network Policies (Zero Trust)

**Default Deny**: All ingress/egress blocked by default
**Explicit Allow**: Only necessary traffic permitted

**Policies**:
- Backend: Ingress from ingress only, Egress to DB/Redis/external APIs
- Frontend: Ingress from ingress only, Egress to backend/external
- Worker: Egress to DB/Redis only
- Scheduler: Egress to DB/Redis only
- Redis: Ingress from backend/worker/scheduler only

**Isolation**:
- Namespace-level isolation
- Service-level communication control
- IP-based restrictions
- Protocol-specific rules

### 3.2 Pod Security Standards

**All Pods**:
- `runAsNonRoot: true`
- `runAsUser: 1001` (non-privileged)
- `readOnlyRootFilesystem: true`
- `allowPrivilegeEscalation: false`
- `capabilities.drop: ALL`
- `seccompProfile: RuntimeDefault`

**Volumes**:
- `emptyDir` for temporary storage
- `emptyDir` for cache
- PVC for persistent storage
- No host mounts

### 3.3 Secrets Management

**External Secrets Operator**:
- AWS Secrets Manager integration
- Automatic secret rotation (1 hour)
- SecretStore configuration
- ExternalSecret resources
- ServiceAccount with IAM role

**Alternative**: HashiCorp Vault support included

### 3.4 Pod Security Admission

**Namespace Labels**:
- `pod-security.kubernetes.io/enforce: baseline`
- `pod-security.kubernetes.io/audit: restricted`
- `pod-security.kubernetes.io/warn: restricted`

---

## 🎨 4. Frontend Security (Vue.js)

### 4.1 XSS Prevention

**Utilities** (`xss.ts`):
- `sanitizeHTML()`: Remove HTML tags
- `sanitizeText()`: Strip all HTML
- `escapeHtml()`: Escape special characters
- `sanitizeUrl()`: Validate URLs (http/https only)
- `containsDangerousContent()`: Detect patterns
- `sanitizeMessage()`: Message-specific sanitization

**Composable** (`useSecureMessage`):
- Safe message display
- Pre-send validation
- Content preparation
- Length limits (1000 chars)

### 4.2 Secure Token Handling

**Utilities** (`security.ts`):
- SessionStorage instead of localStorage
- Token validation
- Secure API wrapper
- CSRF token generation
- Automatic 401 handling
- Credentials omission

**Features**:
- No sensitive data in localStorage
- Automatic token refresh
- Secure fetch wrapper
- CSP violation detection

---

## 📊 5. Observability & Monitoring

### 5.1 Health Endpoints

**Controller**: `HealthController`
- `/health`: Basic health check
- `/health/detailed`: Dependency checks
- `/metrics`: Application metrics

**Checks**:
- Database connectivity
- Redis connectivity
- Cache functionality
- Queue status
- Storage functionality

### 5.2 Structured Logging

**Custom Logger**: `StructuredLogger`
- JSON format for all logs
- Monolog integration
- Production-ready configuration
- Easy parsing by log aggregators

**Log Levels**:
- Emergency, Alert, Critical
- Error, Warning, Notice
- Info, Debug

---

## 💾 6. Database Security

### 6.1 Backup Strategy

**Schedule**:
- Daily incremental backups (02:00 UTC)
- Weekly full backups (Sunday 03:00 UTC)
- Hourly transaction logs
- 30-day retention

**Security**:
- AES-256 encryption at rest
- TLS 1.3 in transit
- Off-site replication
- IAM role-based access

**Recovery**:
- RPO: 15 minutes
- RTO: 1 hour
- Point-in-time recovery
- Automated verification

### 6.2 Database Hardening

**User Permissions**:
- No root access for application
- Least privilege principle
- Separate read/write users
- Connection pooling limits

**Encryption**:
- MySQL 8.0 native encryption
- Transparent Data Encryption (TDE)
- Encrypted backups

---

## 🔧 7. Deployment Security

### 7.1 CI/CD Pipeline

**GitHub Actions**:
- Automated builds
- Container registry push
- Kubernetes deployment
- Secret management
- Rollback capability

**Security**:
- Encrypted secrets
- Branch protection
- Required reviews
- Automated scanning

### 7.2 Container Security

**Dockerfile Best Practices**:
- Multi-stage builds
- Minimal base images (Alpine)
- Non-root user
- No secrets in image
- Scan for vulnerabilities

**Image Registry**:
- GitHub Container Registry
- Image signing
- Vulnerability scanning
- Immutable tags

---

## 📋 8. Security Checklist

### Authentication & Authorization
- ✅ RBAC with 4-level hierarchy
- ✅ Zero Trust model implementation
- ✅ Policies for all entities
- ✅ Role-based middleware
- ✅ Ownership verification

### Data Protection
- ✅ AES-256 message encryption
- ✅ Secure message cleanup
- ✅ Audit logging system
- ✅ Sensitive data sanitization
- ✅ No secrets in code

### Network Security
- ✅ Kubernetes Network Policies
- ✅ Default deny all traffic
- ✅ Namespace isolation
- ✅ Service-level controls
- ✅ IP-based restrictions

### Application Security
- ✅ Input validation (FormRequests)
- ✅ XSS prevention (frontend)
- ✅ CSRF protection
- ✅ Security headers
- ✅ Rate limiting (advanced)

### Infrastructure Security
- ✅ Pod Security Standards
- ✅ Non-root containers
- ✅ Read-only filesystems
- ✅ Capability dropping
- ✅ seccomp profiles

### Secrets Management
- ✅ External Secrets Operator
- ✅ AWS Secrets Manager
- ✅ Automatic rotation
- ✅ No secrets in images
- ✅ IAM role-based access

### Monitoring & Logging
- ✅ Structured JSON logging
- ✅ Health endpoints
- ✅ Metrics endpoint
- ✅ Audit trails
- ✅ Security event logging

### Backup & Recovery
- ✅ Automated daily backups
- ✅ Weekly full backups
- ✅ Point-in-time recovery
- ✅ Off-site replication
- ✅ Regular testing

---

## 🚀 Deployment Instructions

### 1. Apply Migrations

```bash
cd backend
php artisan migrate --force
```

### 2. Register Middleware

Add to `app/Http/Kernel.php`:
```php
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\AuditLog::class,
    \App\Http\Middleware\SecurityHeaders::class,
];

protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\AdvancedRateLimit::class,
        \App\Http\Middleware\AntiSpam::class,
    ],
    
    'auth' => [
        \App\Http\Middleware\BruteForceProtection::class,
    ],
];
```

### 3. Register Routes

Add to `routes/web.php`:
```php
require __DIR__.'/../routes/health.php';
```

### 4. Deploy Kubernetes Manifests

```bash
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/network-policies.yaml
kubectl apply -f k8s/pod-security-policy.yaml
kubectl apply -f k8s/externalsecrets.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/secret.yaml
kubectl apply -f k8s/pvc.yaml
kubectl apply -f k8s/backend-deployment.yaml
kubectl apply -f k8s/frontend-deployment.yaml
kubectl apply -f k8s/worker-deployment.yaml
kubectl apply -f k8s/scheduler-deployment.yaml
kubectl apply -f k8s/services.yaml
kubectl apply -f k8s/hpa.yaml
kubectl apply -f k8s/ingress.yaml
```

### 5. Configure External Secrets

Install External Secrets Operator:
```bash
kubectl apply -f https://raw.githubusercontent.com/external-secrets/external-secrets/main/deploy/kubernetes/external-secrets.yaml
```

Update AWS Secrets Manager with required secrets.

---

## 📚 Additional Documentation

- [Deployment Guide](./DEPLOYMENT.md)
- [Architecture Overview](./ARCHITECTURE.md)
- [Database Backup Strategy](./DB_BACKUP_STRATEGY.md)

---

**Version**: 2.0.0 (Security-Hardened)  
**Last Updated**: 2026-06-26  
**Security Level**: Production-Grade  
**Compliance**: Zero Trust Model
