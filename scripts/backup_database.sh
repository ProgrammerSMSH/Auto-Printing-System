#!/bin/bash
# Backup database script

BACKUP_DIR="/home/backups/print-system"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="print_system"
DB_USER="print_user"
DB_PASS="secure_password"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Backup uploads directory
tar -czf $BACKUP_DIR/uploads_backup_$DATE.tar.gz -C /var/www/printing-system/ci4-backend/public uploads

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/db_backup_$DATE.sql.gz"
