#!/bin/bash
# Restore database script

BACKUP_FILE=$1
DB_NAME="print_system"
DB_USER="print_user"
DB_PASS="secure_password"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "Restoring database from $BACKUP_FILE..."
gunzip -c $BACKUP_FILE | mysql -u $DB_USER -p$DB_PASS $DB_NAME

if [ $? -eq 0 ]; then
    echo "Database restored successfully"
else
    echo "Database restore failed"
    exit 1
fi
