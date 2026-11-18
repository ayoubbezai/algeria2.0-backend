#!/usr/bin/env bash
# ==============================================================================
# CASEFILE 404 — Automated VPS Backend Deployment Script
# Target Domain: api-algeria20.dentdz.com
#
# Usage:
#   chmod +x deploy.sh
#   ./deploy.sh [domain] [ssl-email]
#
# Examples:
#   ./deploy.sh
#   ./deploy.sh api-algeria20.dentdz.com admin@dentdz.com
# ==============================================================================

set -euo pipefail

# Default domain is configured to api-algeria20.dentdz.com
DOMAIN="${1:-api-algeria20.dentdz.com}"
SSL_EMAIL="${2:-}"
BACKEND_PORT="8080"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=================================================="
echo "    CASEFILE 404 — VPS BACKEND DEPLOYMENT        "
echo "=================================================="
echo "Project Directory : ${PROJECT_DIR}"
echo "Configuring Domain: ${DOMAIN}"

# 1. Check prerequisites
echo "--> [1/6] Checking Docker and Compose..."
if ! command -v docker &> /dev/null; then
    echo "Docker not found. Installing docker via official script..."
    curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
    sh /tmp/get-docker.sh
    systemctl enable --now docker
fi

if docker compose version &> /dev/null; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose &> /dev/null; then
    COMPOSE_CMD="docker-compose"
else
    echo "Error: docker compose is required."
    exit 1
fi

# 2. Environment Configuration Check
echo "--> [2/6] Verifying .env exists..."
if [ ! -f "${PROJECT_DIR}/.env" ]; then
    echo "ERROR: .env file not found in ${PROJECT_DIR}!"
    echo "Please create ${PROJECT_DIR}/.env before running ./deploy.sh"
    exit 1
fi

# Ensure production domain & settings in .env
if [ -f "${PROJECT_DIR}/.env" ]; then
    sed -i.bak "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" "${PROJECT_DIR}/.env" 2>/dev/null || true
    sed -i.bak "s|^APP_ENV=.*|APP_ENV=production|" "${PROJECT_DIR}/.env" 2>/dev/null || true
    sed -i.bak "s|^APP_DEBUG=.*|APP_DEBUG=false|" "${PROJECT_DIR}/.env" 2>/dev/null || true
fi

# Ensure APP_KEY exists
if ! grep -q "^APP_KEY=base64:" "${PROJECT_DIR}/.env" 2>/dev/null; then
    RAND_KEY=$(head -c 32 /dev/urandom | base64)
    if grep -q "^APP_KEY=" "${PROJECT_DIR}/.env"; then
        sed -i.bak "s|^APP_KEY=.*|APP_KEY=base64:${RAND_KEY}|" "${PROJECT_DIR}/.env"
    else
        echo "APP_KEY=base64:${RAND_KEY}" >> "${PROJECT_DIR}/.env"
    fi
fi

# 3. Build & Run Docker Containers
echo "--> [3/6] Starting Docker containers with ${COMPOSE_CMD}..."
cd "${PROJECT_DIR}"
${COMPOSE_CMD} down --remove-orphans || true
${COMPOSE_CMD} up -d db redis
echo "--> Waiting for DB to initialize..."
sleep 10
${COMPOSE_CMD} up -d --build

# 4. Wait for database & Run Migrations + Seeders
echo "--> [4/6] Waiting for MySQL container to become healthy..."
RETRY_COUNT=0
MAX_RETRIES=25
ROOT_PW=$(grep DB_ROOT_PASSWORD .env 2>/dev/null | cut -d '=' -f2 | tr -d '\r' | tr -d '"' | tr -d "'" || echo "casefile_root_pass_2026!")
until ${COMPOSE_CMD} exec -T db mysqladmin ping -h 127.0.0.1 -uroot -p"${ROOT_PW}" --silent &> /dev/null || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
    echo "Waiting for database connection ($((RETRY_COUNT+1))/$MAX_RETRIES)..."
    sleep 3
    RETRY_COUNT=$((RETRY_COUNT+1))
done

echo "--> Running migrations and workshop seeders..."
${COMPOSE_CMD} exec -T app php artisan key:generate --force || true
${COMPOSE_CMD} exec -T app php artisan migrate --force
${COMPOSE_CMD} exec -T app php artisan db:seed --class=DatabaseSeeder --force || true
${COMPOSE_CMD} exec -T app php artisan config:cache || true
${COMPOSE_CMD} exec -T app php artisan route:cache || true

# 5. Configure Host Nginx & Domain
echo "--> [5/6] Configuring Host Nginx for ${DOMAIN}..."
if ! command -v nginx &> /dev/null; then
    echo "Host Nginx not installed. Installing nginx & certbot..."
    if command -v apt-get &> /dev/null; then
        apt-get update && apt-get install -y nginx certbot python3-certbot-nginx
    elif command -v yum &> /dev/null; then
        yum install -y epel-release && yum install -y nginx certbot python3-certbot-nginx
    fi
    systemctl enable --now nginx
fi

NGINX_CONF="/etc/nginx/sites-available/${DOMAIN}.conf"
if [ ! -d "/etc/nginx/sites-available" ]; then
    NGINX_CONF="/etc/nginx/conf.d/${DOMAIN}.conf"
fi

cat << EOF > "${NGINX_CONF}"
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    client_max_body_size 30M;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        proxy_pass http://127.0.0.1:${BACKEND_PORT};
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-Host \$host;
        proxy_set_header X-Forwarded-Port \$server_port;
        proxy_connect_timeout 60s;
        proxy_read_timeout 60s;
    }
}
EOF

if [ -d "/etc/nginx/sites-enabled" ]; then
    ln -sf "${NGINX_CONF}" "/etc/nginx/sites-enabled/${DOMAIN}.conf"
    rm -f /etc/nginx/sites-enabled/default || true
fi

nginx -t && systemctl reload nginx
echo "Host Nginx successfully configured and reloaded for ${DOMAIN}!"

# SSL Certificate issuance via Certbot
if [ -n "${SSL_EMAIL}" ]; then
    echo "--> Setting up Let's Encrypt SSL with Certbot for ${DOMAIN} (email: ${SSL_EMAIL})..."
    certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${SSL_EMAIL}" --redirect || {
        echo "Note: Certbot was unable to complete SSL issuance immediately. Verify your DNS A record points to this VPS and rerun:"
        echo "  certbot --nginx -d ${DOMAIN} --register-unsafely-without-email"
    }
    systemctl reload nginx || true
else
    echo "--> Setting up Let's Encrypt SSL without email for ${DOMAIN}..."
    certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos --register-unsafely-without-email --redirect || {
        echo "Note: Certbot was unable to complete SSL issuance immediately. Verify your DNS A record points to this VPS and rerun:"
        echo "  certbot --nginx -d ${DOMAIN} --register-unsafely-without-email"
    }
    systemctl reload nginx || true
fi

# 6. Verification
echo "--> [6/6] Testing local health endpoint..."
sleep 2
HEALTH=$(curl -s http://127.0.0.1:${BACKEND_PORT}/api/health || true)

echo "=================================================="
echo "    DEPLOYMENT READY!                            "
echo "=================================================="
echo "Domain: http://${DOMAIN} (or https://${DOMAIN})"
echo "Local Healthcheck Response: ${HEALTH}"
echo ""
echo "Docker services:"
${COMPOSE_CMD} ps
echo "=================================================="
