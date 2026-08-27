# 🗄️ Database Backup Strategy - U-map

## 📋 Overview

This document outlines the comprehensive backup and recovery strategy for the U-map application database, ensuring data integrity and business continuity.

## 🎯 Backup Requirements

- **RPO (Recovery Point Objective)**: 15 minutes
- **RTO (Recovery Time Objective)**: 1 hour
- **Retention Period**: 30 days
- **Backup Type**: Incremental daily, full weekly
- **Storage**: Encrypted, off-site replication

## 🔄 Backup Schedule

### Daily Backups (Incremental)
- **Time**: 02:00 UTC (low traffic period)
- **Type**: Incremental backup
- **Retention**: 7 days
- **Storage**: Primary + Secondary region

### Weekly Backups (Full)
- **Time**: Sunday 03:00 UTC
- **Type**: Full backup
- **Retention**: 4 weeks
- **Storage**: Primary + Secondary region + Cold storage

### Hourly Transaction Logs
- **Time**: Every hour
- **Type**: Binary log backup
- **Retention**: 24 hours
- **Purpose**: Point-in-time recovery

## 🛠️ Implementation

### Option 1: AWS RDS Automated Backups

```bash
# Enable automated backups
aws rds modify-db-instance \
  --db-instance-identifier umap-production \
  --backup-retention-period 30 \
  --backup-window 02:00-03:00 \
  --apply-immediately
```

### Option 2: Manual Backup Script

```bash
#!/bin/bash
# backup.sh - Automated MySQL backup script

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/mysql"
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Perform backup
mysqldump \
  --single-transaction \
  --quick \
  --lock-tables=false \
  --all-databases \
  --user=$DB_USER \
  --password=$DB_PASSWORD \
  --host=$DB_HOST \
  | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Upload to S3 (encrypted)
aws s3 cp $BACKUP_DIR/backup_$DATE.sql.gz \
  s3://umap-backups/mysql/backup_$DATE.sql.gz \
  --server-side-encryption AES256

# Clean old backups
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: backup_$DATE.sql.gz"
```

### Option 3: Kubernetes CronJob

```yaml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: mysql-backup
  namespace: umap
spec:
  schedule: "0 2 * * *"  # Daily at 2 AM
  successfulJobsHistoryLimit: 3
  failedJobsHistoryLimit: 1
  jobTemplate:
    spec:
      template:
        spec:
          containers:
          - name: backup
            image: mysql:8.0
            command:
            - /bin/sh
            - -c
            - |
              mysqldump \
                --single-transaction \
                --quick \
                --lock-tables=false \
                --all-databases \
                -h $DB_HOST \
                -u $DB_USER \
                -p$DB_PASSWORD \
                | gzip > /backup/backup_$(date +%Y%m%d_%H%M%S).sql.gz
            env:
            - name: DB_HOST
              valueFrom:
                configMapKeyRef:
                  name: umap-config
                  key: db-host
            - name: DB_USER
              valueFrom:
                secretKeyRef:
                  name: umap-secrets
                  key: db-username
            - name: DB_PASSWORD
              valueFrom:
                secretKeyRef:
                  name: umap-secrets
                  key: db-password
            volumeMounts:
            - name: backup-storage
              mountPath: /backup
          volumes:
          - name: backup-storage
            persistentVolumeClaim:
              claimName: mysql-backup-pvc
          restartPolicy: OnFailure
```

## 🔐 Backup Security

### Encryption
- **At Rest**: AES-256 encryption for all backups
- **In Transit**: TLS 1.3 for backup transfers
- **Key Management**: AWS KMS or HashiCorp Vault

### Access Control
- **IAM Roles**: Least privilege access
- **Network**: VPC endpoints for S3 access
- **Audit**: All backup operations logged

## 📊 Backup Verification

### Automated Verification Script

```bash
#!/bin/bash
# verify_backup.sh - Verify backup integrity

BACKUP_FILE=$1

# Check file exists
if [ ! -f "$BACKUP_FILE" ]; then
  echo "Backup file not found: $BACKUP_FILE"
  exit 1
fi

# Decompress and verify
gunzip -t $BACKUP_FILE
if [ $? -eq 0 ]; then
  echo "Backup file integrity verified: $BACKUP_FILE"
else
  echo "Backup file corrupted: $BACKUP_FILE"
  exit 1
fi

# Test restore (dry-run)
gunzip -c $BACKUP_FILE | mysql --host=test-db --user=test --password=test --dry-run
```

## 🚨 Recovery Procedures

### Scenario 1: Single Table Recovery

```bash
# Extract specific table from backup
gunzip -c backup_20240626_020000.sql.gz | \
  sed -n '/DROP TABLE.*`messages`/,/UNLOCK TABLES;/p' > messages_restore.sql

# Restore table
mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASSWORD \
  umap < messages_restore.sql
```

### Scenario 2: Point-in-Time Recovery

```bash
# Restore from full backup
mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASSWORD \
  umap < full_backup.sql

# Apply binary logs up to specific time
mysqlbinlog --start-datetime="2024-06-26 14:30:00" \
  --stop-datetime="2024-06-26 15:00:00" \
  binlog.000123 | mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASSWORD
```

### Scenario 3: Full Database Recovery

```bash
# Stop application
kubectl scale deployment umap-backend replicas=0 -n umap

# Restore from latest backup
gunzip -c /backups/latest_backup.sql.gz | \
  mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASSWORD umap

# Verify data integrity
mysql --host=$DB_HOST --user=$DB_USER --password=$DB_PASSWORD \
  -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM messages;"

# Restart application
kubectl scale deployment umap-backend replicas=3 -n umap
```

## 📈 Monitoring & Alerts

### Metrics to Monitor
- Backup success rate
- Backup duration
- Backup size
- Recovery time (during drills)
- Storage usage

### Alert Thresholds
- **Backup Failure**: Immediate alert
- **Backup Duration > 1 hour**: Warning
- **Storage Usage > 80%**: Warning
- **Storage Usage > 90%**: Critical

## 🧪 Testing & Drills

### Monthly Recovery Drill
1. Select random backup from previous month
2. Restore to test environment
3. Verify data integrity
4. Measure recovery time
5. Document findings

### Quarterly Full Disaster Recovery Test
1. Simulate complete database failure
2. Execute full recovery procedure
3. Validate application functionality
4. Update documentation based on findings

## 📝 Documentation Requirements

### Backup Log
Each backup must be logged with:
- Timestamp
- Backup type (full/incremental)
- Backup size
- Duration
- Success/failure status
- Backup location

### Recovery Log
Each recovery operation must be logged with:
- Timestamp
- Reason for recovery
- Backup used
- Duration
- Verification results
- Post-recovery validation

## 🔧 Maintenance

### Weekly Tasks
- Review backup logs
- Verify backup storage capacity
- Check for failed backups

### Monthly Tasks
- Test backup restoration
- Review and update retention policy
- Audit backup access logs

### Quarterly Tasks
- Full disaster recovery drill
- Review and update backup strategy
- Evaluate backup tool updates

## 📞 Emergency Contacts

- **Database Administrator**: dba@company.com
- **DevOps Team**: devops@company.com
- **On-Call Engineer**: oncall@company.com

## 📚 Related Documentation

- [Kubernetes Deployment Guide](./DEPLOYMENT.md)
- [Architecture Documentation](./ARCHITECTURE.md)
- [Security Checklist](./SECURITY_CHECKLIST.md)

---

**Version**: 1.0.0  
**Last Updated**: 2026-06-26  
**Owner**: DevOps Team
