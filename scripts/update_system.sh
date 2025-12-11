#!/bin/bash
# System update script

echo "Updating Remote Printing System..."

# Update CI4 backend
cd /var/www/printing-system/ci4-backend
git pull origin main
composer install --no-dev

# Update home server
cd /var/www/printing-system/home-server
git pull origin main

# Run migrations
cd /var/www/printing-system/ci4-backend
php spark migrate

# Restart services
systemctl restart apache2
systemctl restart cups

echo "System update completed"
