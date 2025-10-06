#!/bin/bash
################################################################################
# Ainstein Platform - Automated Installation Script
# Version: 1.0.0
# Last Updated: 2025-10-06
################################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
REQUIRED_PHP_VERSION="8.3"
REQUIRED_NODE_VERSION="18"
REQUIRED_COMPOSER_VERSION="2.6"

################################################################################
# Utility Functions
################################################################################

print_header() {
    echo ""
    echo -e "${PURPLE}╔══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║${NC}  ${CYAN}🚀 Ainstein Platform - Automated Installer${NC}  ${PURPLE}║${NC}"
    echo -e "${PURPLE}╚══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_step() {
    echo -e "${BLUE}➤${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${CYAN}ℹ${NC} $1"
}

# Check command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Version comparison
version_ge() {
    # Returns 0 if $1 >= $2
    printf '%s\n%s' "$2" "$1" | sort -V -C
}

# Ask yes/no question
ask_confirm() {
    while true; do
        read -p "$1 [y/n]: " yn
        case $yn in
            [Yy]* ) return 0;;
            [Nn]* ) return 1;;
            * ) echo "Please answer yes or no.";;
        esac
    done
}

################################################################################
# Environment Checks
################################################################################

check_os() {
    print_step "Checking operating system..."

    OS="$(uname -s)"
    case "${OS}" in
        Linux*)     OS_NAME=Linux;;
        Darwin*)    OS_NAME=macOS;;
        MINGW*|MSYS*|CYGWIN*)    OS_NAME=Windows;;
        *)          OS_NAME="UNKNOWN:${OS}"
    esac

    print_success "Operating System: ${OS_NAME}"
    export OS_NAME
}

check_php() {
    print_step "Checking PHP..."

    if ! command_exists php; then
        print_error "PHP is not installed"
        print_info "Install PHP from: https://www.php.net/downloads"
        exit 1
    fi

    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    print_success "PHP ${PHP_VERSION} installed"

    # Check version
    if ! version_ge "$PHP_VERSION" "$REQUIRED_PHP_VERSION"; then
        print_error "PHP version must be >= ${REQUIRED_PHP_VERSION}"
        print_info "Current version: ${PHP_VERSION}"
        exit 1
    fi

    # Check required extensions
    print_step "Checking PHP extensions..."
    REQUIRED_EXTENSIONS=("mbstring" "xml" "curl" "json" "pdo" "tokenizer" "openssl" "fileinfo" "bcmath")
    MISSING_EXTENSIONS=()

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^${ext}$"; then
            print_success "Extension ${ext}: installed"
        else
            print_error "Extension ${ext}: MISSING"
            MISSING_EXTENSIONS+=("$ext")
        fi
    done

    if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
        print_error "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
        print_info "Install them before continuing"

        # Provide install commands based on OS
        if [ "$OS_NAME" = "Linux" ]; then
            print_info "Ubuntu/Debian: sudo apt install php-${MISSING_EXTENSIONS[0]}"
        elif [ "$OS_NAME" = "macOS" ]; then
            print_info "macOS: brew install php@8.3"
        fi

        exit 1
    fi
}

check_composer() {
    print_step "Checking Composer..."

    if ! command_exists composer; then
        print_error "Composer is not installed"
        print_info "Install from: https://getcomposer.org/download/"

        if ask_confirm "Would you like to install Composer now?"; then
            print_step "Installing Composer..."
            curl -sS https://getcomposer.org/installer | php
            sudo mv composer.phar /usr/local/bin/composer
            print_success "Composer installed"
        else
            exit 1
        fi
    fi

    COMPOSER_VERSION=$(composer -V | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Composer ${COMPOSER_VERSION} installed"
}

check_node() {
    print_step "Checking Node.js..."

    if ! command_exists node; then
        print_error "Node.js is not installed"
        print_info "Install from: https://nodejs.org/"
        exit 1
    fi

    NODE_VERSION=$(node -v | sed 's/v//')
    print_success "Node.js ${NODE_VERSION} installed"

    # Check version
    NODE_MAJOR=$(echo "$NODE_VERSION" | cut -d. -f1)
    if [ "$NODE_MAJOR" -lt "$REQUIRED_NODE_VERSION" ]; then
        print_error "Node.js version must be >= ${REQUIRED_NODE_VERSION}"
        print_info "Current version: ${NODE_VERSION}"
        exit 1
    fi
}

check_npm() {
    print_step "Checking NPM..."

    if ! command_exists npm; then
        print_error "NPM is not installed"
        exit 1
    fi

    NPM_VERSION=$(npm -v)
    print_success "NPM ${NPM_VERSION} installed"
}

check_git() {
    print_step "Checking Git..."

    if ! command_exists git; then
        print_error "Git is not installed"
        print_info "Install from: https://git-scm.com/downloads"
        exit 1
    fi

    GIT_VERSION=$(git --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    print_success "Git ${GIT_VERSION} installed"
}

################################################################################
# Installation Steps
################################################################################

install_dependencies() {
    print_step "Installing PHP dependencies (Composer)..."

    cd ainstein-laravel || exit 1

    if composer install --no-interaction --prefer-dist --optimize-autoloader; then
        print_success "Composer dependencies installed"
    else
        print_error "Failed to install Composer dependencies"
        exit 1
    fi

    cd ..
}

install_node_dependencies() {
    print_step "Installing Node.js dependencies (NPM)..."

    cd ainstein-laravel || exit 1

    if npm install --silent; then
        print_success "NPM dependencies installed"
    else
        print_error "Failed to install NPM dependencies"
        exit 1
    fi

    cd ..
}

setup_environment() {
    print_step "Setting up environment configuration..."

    cd ainstein-laravel || exit 1

    if [ ! -f .env ]; then
        cp .env.example .env
        print_success ".env file created"
    else
        print_warning ".env file already exists, skipping"
    fi

    # Generate application key
    php artisan key:generate --force
    print_success "Application key generated"

    cd ..
}

configure_database() {
    print_step "Configuring database..."

    cd ainstein-laravel || exit 1

    echo ""
    print_info "Choose database type:"
    echo "1) SQLite (recommended for development)"
    echo "2) MySQL (recommended for production)"
    read -p "Enter choice [1-2]: " db_choice

    case $db_choice in
        1)
            # SQLite
            print_step "Setting up SQLite database..."

            # Update .env
            sed -i.bak 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env

            # Create database file
            touch database/database.sqlite

            print_success "SQLite database created"
            ;;
        2)
            # MySQL
            print_step "Setting up MySQL database..."

            read -p "MySQL host [127.0.0.1]: " mysql_host
            mysql_host=${mysql_host:-127.0.0.1}

            read -p "MySQL port [3306]: " mysql_port
            mysql_port=${mysql_port:-3306}

            read -p "MySQL database name [ainstein]: " mysql_db
            mysql_db=${mysql_db:-ainstein}

            read -p "MySQL username [root]: " mysql_user
            mysql_user=${mysql_user:-root}

            read -sp "MySQL password: " mysql_pass
            echo ""

            # Update .env
            sed -i.bak "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
            sed -i.bak "s/^DB_HOST=.*/DB_HOST=${mysql_host}/" .env
            sed -i.bak "s/^DB_PORT=.*/DB_PORT=${mysql_port}/" .env
            sed -i.bak "s/^DB_DATABASE=.*/DB_DATABASE=${mysql_db}/" .env
            sed -i.bak "s/^DB_USERNAME=.*/DB_USERNAME=${mysql_user}/" .env
            sed -i.bak "s/^DB_PASSWORD=.*/DB_PASSWORD=${mysql_pass}/" .env

            # Create database if MySQL is available
            if command_exists mysql; then
                mysql -h"${mysql_host}" -P"${mysql_port}" -u"${mysql_user}" -p"${mysql_pass}" \
                    -e "CREATE DATABASE IF NOT EXISTS ${mysql_db} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
                if [ $? -eq 0 ]; then
                    print_success "MySQL database created"
                else
                    print_warning "Could not create database automatically, create it manually"
                fi
            fi
            ;;
        *)
            print_error "Invalid choice"
            exit 1
            ;;
    esac

    cd ..
}

configure_openai() {
    print_step "Configuring OpenAI API..."

    cd ainstein-laravel || exit 1

    echo ""
    print_info "OpenAI API Key is required for AI features"
    print_info "Get your key from: https://platform.openai.com/api-keys"
    echo ""

    read -p "Enter OpenAI API key (or press Enter to use mock service): " openai_key

    if [ -n "$openai_key" ]; then
        sed -i.bak "s/^OPENAI_API_KEY=.*/OPENAI_API_KEY=${openai_key}/" .env
        print_success "OpenAI API key configured"
    else
        sed -i.bak "s/^OPENAI_API_KEY=.*/OPENAI_API_KEY=fake-key-for-testing/" .env
        print_warning "Using mock service (for testing only)"
    fi

    cd ..
}

run_migrations() {
    print_step "Running database migrations..."

    cd ainstein-laravel || exit 1

    if php artisan migrate --force --seed; then
        print_success "Database migrations completed"
        print_success "Seed data inserted (1 tenant, 3 users, 4 prompts)"
    else
        print_error "Failed to run migrations"
        exit 1
    fi

    cd ..
}

build_assets() {
    print_step "Building frontend assets..."

    cd ainstein-laravel || exit 1

    if npm run build; then
        print_success "Frontend assets built"
    else
        print_error "Failed to build frontend assets"
        exit 1
    fi

    cd ..
}

set_permissions() {
    print_step "Setting file permissions..."

    cd ainstein-laravel || exit 1

    if [ "$OS_NAME" != "Windows" ]; then
        chmod -R 775 storage bootstrap/cache 2>/dev/null || chmod -R 777 storage bootstrap/cache
        print_success "Permissions set"
    else
        print_info "Skipping permissions (Windows)"
    fi

    cd ..
}

################################################################################
# Verification
################################################################################

verify_installation() {
    print_step "Verifying installation..."

    cd ainstein-laravel || exit 1

    # Check database connection
    print_step "Checking database connection..."
    TENANT_COUNT=$(php artisan tinker --execute="echo App\Models\Tenant::count();" 2>/dev/null | tail -1)

    if [ "$TENANT_COUNT" -ge 1 ]; then
        print_success "Database connection: OK (${TENANT_COUNT} tenant found)"
    else
        print_warning "Database might not be properly set up"
    fi

    # Check if assets exist
    if [ -f "public/build/manifest.json" ]; then
        print_success "Frontend assets: OK"
    else
        print_warning "Frontend assets might not be built correctly"
    fi

    cd ..
}

run_tests() {
    print_step "Running tests..."

    cd ainstein-laravel || exit 1

    if ask_confirm "Would you like to run tests now?"; then
        php artisan test
    else
        print_info "Skipping tests"
    fi

    cd ..
}

################################################################################
# Final Steps
################################################################################

display_credentials() {
    echo ""
    echo -e "${PURPLE}╔══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║${NC}  ${GREEN}✅ Installation Complete!${NC}  ${PURPLE}║${NC}"
    echo -e "${PURPLE}╚══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}📋 Default Credentials:${NC}"
    echo ""
    echo -e "  ${YELLOW}Demo Tenant Admin:${NC}"
    echo -e "    Email:    ${GREEN}admin@demo.com${NC}"
    echo -e "    Password: ${GREEN}password${NC}"
    echo ""
    echo -e "  ${YELLOW}Demo Tenant Member:${NC}"
    echo -e "    Email:    ${GREEN}member@demo.com${NC}"
    echo -e "    Password: ${GREEN}password${NC}"
    echo ""
    echo -e "${CYAN}🚀 Start the development server:${NC}"
    echo ""
    echo -e "  ${GREEN}cd ainstein-laravel${NC}"
    echo -e "  ${GREEN}php artisan serve${NC}"
    echo ""
    echo -e "${CYAN}🌐 Then visit:${NC}"
    echo ""
    echo -e "  ${BLUE}http://localhost:8000${NC}"
    echo ""
    echo -e "${CYAN}📚 Documentation:${NC}"
    echo ""
    echo -e "  - ARCHITECTURE-OVERVIEW.md     (Complete technical architecture)"
    echo -e "  - DEVELOPMENT-ROADMAP.md       (6-month development plan)"
    echo -e "  - SESSION-REPORT-2025-10-06.md (Latest changes)"
    echo ""
    echo -e "${YELLOW}⚠️  Remember to change default passwords in production!${NC}"
    echo ""
}

start_server() {
    if ask_confirm "Would you like to start the development server now?"; then
        print_step "Starting Laravel development server..."
        cd ainstein-laravel || exit 1
        echo ""
        print_success "Server starting on http://localhost:8000"
        print_info "Press Ctrl+C to stop"
        echo ""
        php artisan serve
    fi
}

################################################################################
# Main Installation Flow
################################################################################

main() {
    print_header

    # Environment checks
    check_os
    check_php
    check_composer
    check_node
    check_npm
    check_git

    echo ""
    echo -e "${GREEN}✓ All system requirements met!${NC}"
    echo ""

    if ! ask_confirm "Continue with installation?"; then
        print_info "Installation cancelled"
        exit 0
    fi

    # Installation steps
    echo ""
    echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${PURPLE}  Installation Steps${NC}"
    echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""

    install_dependencies
    install_node_dependencies
    setup_environment
    configure_database
    configure_openai
    run_migrations
    build_assets
    set_permissions

    # Verification
    echo ""
    echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${PURPLE}  Verification${NC}"
    echo -e "${PURPLE}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""

    verify_installation
    run_tests

    # Display final info
    display_credentials
    start_server
}

# Run main function
main "$@"
