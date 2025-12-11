#!/bin/bash

# Remote Printing System - Installation Script
# Run this script on a fresh Ubuntu/Debian server

set -e

echo "==========================================="
echo " Remote PDF Printing System Installation"
echo "==========================================="

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root" 
   exit 1
fi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    local status=$1
    local message=$2
    
    case $status in
        "success")
            echo -e "${GREEN}[✓] ${message}${NC}"
            ;;
        "error")
            echo -e "${RED}[✗] ${message}${NC}"
            ;;
        "info")
            echo -e "${YELLOW}[i] ${message}${NC}"
            ;;
    esac
}

# Update system
print_status "info" "Updating system packages..."
apt update && apt upgrade -y

# Install PHP and extensions
print_status "info" "Installing PHP and extensions..."
apt install -y php8.1 php8.1-cli php8.1-curl php8.1-mbstring php8.1-intl \
               php8.1-xml php8.1-zip php8.1-mysql php8.1-gd

# Install Apache
print_status "info" "Installing Apache..."
apt install -y apache2 libapache2-mod-php8.1
a2enmod rewrite headers

# Install MySQL
print_status "info" "Installing MySQL..."
apt install -y mysql-server

# Install Python and CUPS
print_status "info" "Installing Python and CUPS..."
apt install -y python3 python3-pip cups cups-client

# Install Python dependencies
print_status "info" "Installing Python dependencies..."
pip3 install pycups qrcode[pil] pillow requests

# Install Composer
print_status "info" "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create project directory
print_status "info" "Creating project directories..."
mkdir -p /var/www/printing-system
cd /var/www/printing-system

# Clone or create directory structure
mkdir -p {ci4-backend,home-server}

print_status "success" "Base system installation complete!"

# Configure MySQL
print_status "info" "Configuring MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS print_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'print_user'@'localhost' IDENTIFIED BY 'secure_password_123';"
mysql -e "GRANT ALL PRIVILEGES ON print_system.* TO 'print_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Configure Apache
print_status "info" "Configuring Apache..."
cat > /etc/apache2/sites-available/printing-system.conf << 'EOF'
<VirtualHost *:80>
    ServerName printing.your-domain.com
    ServerAdmin admin@your-domain.com
    DocumentRoot /var/www/printing-system/ci4-backend/public
    
    <Directory /var/www/printing-system/ci4-backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Security headers
        Header set X-Content-Type-Options "nosniff"
        Header set X-Frame-Options "DENY"
        Header set X-XSS-Protection "1; mode=block"
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/printing-system-error.log
    CustomLog ${APACHE_LOG_DIR}/printing-system-access.log combined
</VirtualHost>
EOF

a2ensite printing-system.conf
a2dissite 000-default.conf
systemctl reload apache2

# Configure CUPS
print_status "info" "Configuring CUPS..."
usermod -a -G lpadmin www-data
systemctl restart cups

# Create cron job for home server
print_status "info" "Creating cron job..."
cat > /etc/cron.d/remote-printing << 'EOF'
# Check for print jobs every minute
* * * * * www-data /usr/bin/php /var/www/printing-system/home-server/cron.php >> /var/www/printing-system/home-server/logs/cron.log 2>&1

# Daily cleanup at 3 AM
0 3 * * * www-data /usr/bin/php /var/www/printing-system/ci4-backend/public/index.php cleanup >> /var/www/printing-system/home-server/logs/cleanup.log 2>&1

# Health check every 5 minutes
*/5 * * * * root /var/www/printing-system/home-server/health_check.sh >> /var/www/printing-system/home-server/logs/health.log 2>&1
EOF

# Set permissions
print_status "info" "Setting permissions..."
chown -R www-data:www-data /var/www/printing-system
chmod -R 755 /var/www/printing-system
chmod -R 775 /var/www/printing-system/ci4-backend/writable
chmod -R 775 /var/www/printing-system/home-server/logs
chmod -R 775 /var/www/printing-system/home-server/temp

# Create SSL certificate (Let's Encrypt)
print_status "info" "Would you like to install SSL certificate with Let's Encrypt? (y/n)"
read -r ssl_choice
if [[ $ssl_choice == "y" ]]; then
    apt install -y certbot python3-certbot-apache
    certbot --apache -d printing.your-domain.com
fi

print_status "success" "Installation complete!"
echo ""
print_status "info" "Next steps:"
echo "1. Copy your source code to /var/www/printing-system/"
echo "2. Configure .env file in ci4-backend directory"
echo "3. Configure config.php in home-server directory"
echo "4. Run migrations: cd /var/www/printing-system/ci4-backend && php spark migrate"
echo "5. Test the system by uploading a PDF"
echo ""
print_status "info" "Access your system at: http://printing.your-domain.com"
