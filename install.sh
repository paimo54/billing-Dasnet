#!/bin/bash

#############################################
# Billing Management System - Auto Installer
# For aaPanel (CentOS/Ubuntu/Debian)
# Version: 2.4.0
#############################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="billing-dasnet"
APP_DIR="/www/wwwroot/${APP_NAME}"
DB_NAME="billing_dasnet"
DB_USER="billing_user"
DB_PASS=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 20)
DOMAIN=""
PHP_VERSION="8.1"

# Functions
print_header() {
    echo -e "${BLUE}"
    echo "=============================================="
    echo "  Billing Management System - Auto Installer"
    echo "  Version: 2.4.0"
    echo "=============================================="
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}➜ $1${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run as root (use sudo)"
        exit 1
    fi
}

check_aapanel() {
    if [ ! -d "/www/server" ]; then
        print_error "aaPanel not detected. Please install aaPanel first."
        echo "Visit: https://www.aapanel.com/new/download.html"
        exit 1
    fi
    print_success "aaPanel detected"
}

get_user_input() {
    print_info "Please provide the following information:"
    echo ""

    read -p "Domain name (e.g., billing.yourdomain.com): " DOMAIN
    if [ -z "$DOMAIN" ]; then
        print_error "Domain name is required"
        exit 1
    fi

    read -p "Database name [billing_dasnet]: " input_db_name
    DB_NAME=${input_db_name:-$DB_NAME}

    read -p "Database user [billing_user]: " input_db_user
    DB_USER=${input_db_user:-$DB_USER}

    read -p "PHP version [8.1]: " input_php_version
    PHP_VERSION=${input_php_version:-$PHP_VERSION}

    echo ""
    print_info "Configuration:"
    echo "  Domain: $DOMAIN"
    echo "  App Directory: $APP_DIR"
    echo "  Database: $DB_NAME"
    echo "  DB User: $DB_USER"
    echo "  DB Password: $DB_PASS (auto-generated)"
    echo "  PHP Version: $PHP_VERSION"
    echo ""

    read -p "Continue with installation? (y/n): " confirm
    if [ "$confirm" != "y" ]; then
        print_error "Installation cancelled"
        exit 1
    fi
}

install_dependencies() {
    print_info "Installing system dependencies..."

    # Detect OS
    if [ -f /etc/redhat-release ]; then
        # CentOS/RHEL
        yum install -y git unzip curl wget
    else
        # Ubuntu/Debian
        apt-get update
        apt-get install -y git unzip curl wget
    fi

    print_success "System dependencies installed"
}

create_database() {
    print_info "Creating database..."

    # Get MySQL root password from aaPanel
    MYSQL_ROOT_PASS=$(cat /www/server/panel/default.pl | grep "mysql_root" | awk -F"'" '{print $2}')

    if [ -z "$MYSQL_ROOT_PASS" ]; then
        print_error "Could not get MySQL root password from aaPanel"
        read -sp "Please enter MySQL root password: " MYSQL_ROOT_PASS
        echo ""
    fi

    # Create database and user
    mysql -uroot -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

    print_success "Database created: $DB_NAME"
}

create_website() {
    print_info "Creating website in aaPanel..."

    # Create directory
    mkdir -p "$APP_DIR"

    # Set permissions
    chown -R www:www "$APP_DIR"
    chmod -R 755 "$APP_DIR"

    print_success "Website directory created: $APP_DIR"
}

upload_application() {
    print_info "Uploading application files..."

    # Check if files are in current directory
    if [ -f "composer.json" ] && [ -f "artisan" ]; then
        print_info "Copying files from current directory..."
        cp -r . "$APP_DIR/"
    else
        print_error "Application files not found in current directory"
        print_info "Please upload your application files to: $APP_DIR"
        read -p "Press Enter after uploading files..."
    fi

    # Remove installer script from app directory
    rm -f "$APP_DIR/install.sh"

    print_success "Application files uploaded"
}

install_composer() {
    print_info "Installing Composer dependencies..."

    cd "$APP_DIR"

    # Check if composer is installed
    if ! command -v composer &> /dev/null; then
        print_info "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    fi

    # Install dependencies
    composer install --no-dev --optimize-autoloader

    print_success "Composer dependencies installed"
}

configure_environment() {
    print_info "Configuring environment..."

    cd "$APP_DIR"

    # Copy .env file
    if [ ! -f ".env" ]; then
        cp .env.example .env
    fi

    # Generate app key
    php artisan key:generate --force

    # Update .env file
    sed -i "s|APP_ENV=.*|APP_ENV=production|g" .env
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|g" .env
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|g" .env

    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|g" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|g" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|g" .env

    # Set queue to database
    sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|g" .env

    print_success "Environment configured"
}

run_migrations() {
    print_info "Running database migrations..."

    cd "$APP_DIR"

    # Create queue table
    php artisan queue:table

    # Run migrations
    php artisan migrate --force

    print_success "Database migrations completed"
}

optimize_application() {
    print_info "Optimizing application..."

    cd "$APP_DIR"

    # Clear caches
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear

    # Optimize
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    print_success "Application optimized"
}

set_permissions() {
    print_info "Setting permissions..."

    cd "$APP_DIR"

    # Set ownership
    chown -R www:www "$APP_DIR"

    # Set directory permissions
    find "$APP_DIR" -type d -exec chmod 755 {} \;
    find "$APP_DIR" -type f -exec chmod 644 {} \;

    # Set writable directories
    chmod -R 775 "$APP_DIR/storage"
    chmod -R 775 "$APP_DIR/bootstrap/cache"

    print_success "Permissions set"
}

configure_nginx() {
    print_info "Configuring Nginx..."

    # Create Nginx config
    cat > "/www/server/panel/vhost/nginx/${DOMAIN}.conf" <<EOF
server {
    listen 80;
    listen 443 ssl http2;
    server_name ${DOMAIN};

    root ${APP_DIR}/public;
    index index.php index.html;

    # SSL Configuration (will be configured by aaPanel SSL)
    # ssl_certificate /path/to/cert.pem;
    # ssl_certificate_key /path/to/key.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /www/wwwlogs/${DOMAIN}.log;
    error_log /www/wwwlogs/${DOMAIN}.error.log;

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-${PHP_VERSION}.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Laravel routing
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to .env
    location ~ /\.env {
        deny all;
    }
}
EOF

    # Reload Nginx
    nginx -t && systemctl reload nginx

    print_success "Nginx configured"
}

setup_supervisor() {
    print_info "Setting up Supervisor for queue worker..."

    # Install supervisor if not exists
    if ! command -v supervisorctl &> /dev/null; then
        if [ -f /etc/redhat-release ]; then
            yum install -y supervisor
        else
            apt-get install -y supervisor
        fi
        systemctl enable supervisor
        systemctl start supervisor
    fi

    # Create supervisor config
    cat > "/etc/supervisor/conf.d/${APP_NAME}-worker.conf" <<EOF
[program:${APP_NAME}-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www
numprocs=4
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

    # Reload supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start "${APP_NAME}-worker:*"

    print_success "Supervisor configured"
}

setup_cron() {
    print_info "Setting up cron job..."

    # Add cron job for Laravel scheduler
    (crontab -l 2>/dev/null | grep -v "${APP_DIR}/artisan schedule:run"; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

    print_success "Cron job configured"
}

create_admin_user() {
    print_info "Creating admin user..."

    cd "$APP_DIR"

    # Seed database (if seeder exists)
    if [ -f "database/seeders/DatabaseSeeder.php" ]; then
        php artisan db:seed --force
        print_success "Database seeded with default users"
    else
        print_info "No seeder found. You'll need to create admin user manually."
    fi
}

print_completion() {
    echo ""
    echo -e "${GREEN}"
    echo "=============================================="
    echo "  Installation Completed Successfully!"
    echo "=============================================="
    echo -e "${NC}"
    echo ""
    echo "Application Details:"
    echo "  URL: https://${DOMAIN}"
    echo "  Directory: ${APP_DIR}"
    echo ""
    echo "Database Details:"
    echo "  Database: ${DB_NAME}"
    echo "  Username: ${DB_USER}"
    echo "  Password: ${DB_PASS}"
    echo ""
    echo "Default Login (if seeded):"
    echo "  SuperAdmin: superadmin@example.com / password"
    echo "  Admin: admin@example.com / password"
    echo "  Technician: technician@example.com / password"
    echo ""
    echo "Next Steps:"
    echo "  1. Configure SSL certificate in aaPanel"
    echo "  2. Update .env file with payment gateway credentials"
    echo "  3. Configure FreeRADIUS (if using network management)"
    echo "  4. Change default passwords"
    echo ""
    echo "Important Files:"
    echo "  Environment: ${APP_DIR}/.env"
    echo "  Logs: ${APP_DIR}/storage/logs/"
    echo "  Nginx Config: /www/server/panel/vhost/nginx/${DOMAIN}.conf"
    echo ""
    echo "Save this information securely!"
    echo ""

    # Save credentials to file
    cat > "${APP_DIR}/INSTALLATION_INFO.txt" <<EOF
Billing Management System - Installation Info
==============================================

Installation Date: $(date)

Application Details:
  URL: https://${DOMAIN}
  Directory: ${APP_DIR}

Database Details:
  Database: ${DB_NAME}
  Username: ${DB_USER}
  Password: ${DB_PASS}

Default Login (if seeded):
  SuperAdmin: superadmin@example.com / password
  Admin: admin@example.com / password
  Technician: technician@example.com / password

IMPORTANT: Change all default passwords immediately!
EOF

    chmod 600 "${APP_DIR}/INSTALLATION_INFO.txt"

    print_success "Installation info saved to: ${APP_DIR}/INSTALLATION_INFO.txt"
}

# Main installation process
main() {
    print_header

    check_root
    check_aapanel
    get_user_input

    print_info "Starting installation..."
    echo ""

    install_dependencies
    create_database
    create_website
    upload_application
    install_composer
    configure_environment
    run_migrations
    optimize_application
    set_permissions
    configure_nginx
    setup_supervisor
    setup_cron
    create_admin_user

    print_completion
}

# Run main function
main
