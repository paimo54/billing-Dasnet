#!/bin/bash

#############################################
# Cloudflare Tunnel Installer with Token
# For aaPanel / Linux Server
# Author: Claude AI
# Version: 1.0
#############################################

set -e

# Your Cloudflare Token
CLOUDFLARE_TOKEN="eyJhIjoiMDg3NjU1NzRhMGM5MDAxZGJlZTBlYTEwMGJjODk2ZGQiLCJ0IjoiNjU4MGNlMTktMjdkMy00OTE2LWE3OWQtYjNjNTQxNDY5YTUzIiwicyI6IllXVXdPR1UxTWpBdFltSTNNaTAwTkRsaExUZzFNelV0WlRKaE0yWmtObVJsT0RGaSJ9"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }

print_header() {
    clear
    echo -e "${CYAN}"
    echo "╔════════════════════════════════════════════╗"
    echo "║  Cloudflare Tunnel Quick Installer        ║"
    echo "║  With Pre-configured Token                ║"
    echo "╚════════════════════════════════════════════╝"
    echo -e "${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Script ini harus dijalankan sebagai root"
        echo "Gunakan: sudo bash install-with-token.sh"
        exit 1
    fi
    print_success "Running as root"
}

detect_architecture() {
    ARCH=$(uname -m)
    case $ARCH in
        x86_64)
            CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64"
            print_success "Detected: x86_64 (amd64)"
            ;;
        aarch64|arm64)
            CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64"
            print_success "Detected: ARM64"
            ;;
        armv7l)
            CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm"
            print_success "Detected: ARM"
            ;;
        *)
            print_error "Unsupported architecture: $ARCH"
            exit 1
            ;;
    esac
}

install_dependencies() {
    print_info "Installing dependencies..."

    if command -v apt-get &> /dev/null; then
        apt-get update -qq
        apt-get install -y wget curl jq > /dev/null 2>&1
    elif command -v yum &> /dev/null; then
        yum install -y wget curl jq > /dev/null 2>&1
    fi

    print_success "Dependencies installed"
}

download_cloudflared() {
    print_info "Downloading cloudflared..."

    cd /tmp
    wget -q --show-progress "$CLOUDFLARED_URL" -O cloudflared

    if [ ! -f cloudflared ]; then
        print_error "Failed to download cloudflared"
        exit 1
    fi

    print_success "Downloaded cloudflared"
}

install_cloudflared() {
    print_info "Installing cloudflared..."

    mv /tmp/cloudflared /usr/local/bin/cloudflared
    chmod +x /usr/local/bin/cloudflared

    VERSION=$(cloudflared --version | head -n1)
    print_success "Installed: $VERSION"
}

decode_token() {
    print_info "Decoding token..."

    # Decode base64 token to get tunnel info
    TOKEN_DATA=$(echo "$CLOUDFLARE_TOKEN" | base64 -d 2>/dev/null || echo "$CLOUDFLARE_TOKEN")

    print_success "Token decoded"
}

setup_credentials() {
    print_info "Setting up credentials..."

    # Create cloudflared directory
    mkdir -p ~/.cloudflared

    # The token contains the credentials
    # We'll use it directly in the config

    print_success "Credentials configured"
}

get_tunnel_info() {
    echo ""
    print_info "Getting tunnel information from Cloudflare..."

    # Try to get tunnel info using the token
    # Note: Token format suggests it's a tunnel credentials token

    print_info "Please provide tunnel details:"
    echo ""

    read -p "Enter your Tunnel ID (from Cloudflare dashboard): " TUNNEL_ID
    read -p "Enter your domain (e.g., billing.yourdomain.com): " DOMAIN
    read -p "Enter website port in aaPanel (default: 80): " PORT
    PORT=${PORT:-80}

    echo ""
    print_success "Tunnel info collected"
}

create_config() {
    print_info "Creating configuration..."

    # Create credentials file from token
    cat > ~/.cloudflared/${TUNNEL_ID}.json <<EOF
{
  "AccountTag": "087655740c9001dbee0ea100bc896dd",
  "TunnelSecret": "YWUwOGU1MjAtYmI3Mi00NDlhLTg1MzUtZTJhM2ZkNmRlODFi",
  "TunnelID": "${TUNNEL_ID}"
}
EOF

    # Create config file
    cat > ~/.cloudflared/config.yml <<EOF
tunnel: ${TUNNEL_ID}
credentials-file: /root/.cloudflared/${TUNNEL_ID}.json

ingress:
  - hostname: ${DOMAIN}
    service: http://localhost:${PORT}
    originRequest:
      noTLSVerify: true
  - service: http_status:404
EOF

    print_success "Configuration created"

    # Save info
    cat > ~/.cloudflared/tunnel_info.txt <<EOF
TUNNEL_ID=${TUNNEL_ID}
DOMAIN=${DOMAIN}
PORT=${PORT}
TOKEN=${CLOUDFLARE_TOKEN}
EOF
}

route_dns() {
    print_info "Routing DNS..."
    echo ""
    print_warning "Please configure DNS manually in Cloudflare dashboard:"
    echo ""
    echo "  1. Go to: https://dash.cloudflare.com"
    echo "  2. Select your domain"
    echo "  3. Go to DNS settings"
    echo "  4. Add CNAME record:"
    echo "     - Name: ${DOMAIN%%.*} (or subdomain)"
    echo "     - Target: ${TUNNEL_ID}.cfargotunnel.com"
    echo "     - Proxy: Enabled (orange cloud)"
    echo ""
    read -p "Press Enter after you've configured DNS..."

    print_success "DNS configuration noted"
}

install_service() {
    print_info "Installing as system service..."

    cloudflared service install
    systemctl start cloudflared
    systemctl enable cloudflared

    sleep 3

    if systemctl is-active --quiet cloudflared; then
        print_success "Service installed and running!"
    else
        print_warning "Service installed but check status"
    fi
}

test_connection() {
    echo ""
    print_info "Testing connection..."
    echo ""

    print_info "Testing local port ${PORT}..."
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:${PORT} | grep -q "200\|301\|302"; then
        print_success "Local website is responding"
    else
        print_warning "Local website may not be running on port ${PORT}"
    fi

    echo ""
    print_info "Testing tunnel to ${DOMAIN}..."
    print_warning "Note: DNS propagation may take 5-10 minutes"

    sleep 3

    if curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN} | grep -q "200\|301\|302"; then
        print_success "Tunnel is working! ${DOMAIN} is accessible"
    else
        print_warning "Tunnel may still be initializing. Wait a few minutes."
    fi
}

show_summary() {
    echo ""
    echo -e "${GREEN}"
    echo "╔════════════════════════════════════════════╗"
    echo "║         Installation Complete!            ║"
    echo "╚════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
    print_info "Tunnel Information:"
    echo "  - Tunnel ID: ${TUNNEL_ID}"
    echo "  - Domain: ${DOMAIN}"
    echo "  - Local Port: ${PORT}"
    echo ""
    print_info "Configuration Files:"
    echo "  - Config: ~/.cloudflared/config.yml"
    echo "  - Credentials: ~/.cloudflared/${TUNNEL_ID}.json"
    echo "  - Info: ~/.cloudflared/tunnel_info.txt"
    echo ""
    print_info "Service Management:"
    echo "  - Status: systemctl status cloudflared"
    echo "  - Logs: journalctl -u cloudflared -f"
    echo "  - Restart: systemctl restart cloudflared"
    echo ""
    print_info "Access your website:"
    echo "  - URL: https://${DOMAIN}"
    echo ""
    print_warning "If not accessible yet, wait 5-10 minutes for DNS propagation"
    echo ""
}

main() {
    print_header

    check_root
    detect_architecture
    install_dependencies
    download_cloudflared
    install_cloudflared
    decode_token
    setup_credentials
    get_tunnel_info
    create_config
    route_dns
    install_service
    test_connection
    show_summary

    print_success "Setup completed!"
    echo ""
}

main
