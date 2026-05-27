#!/bin/bash

#############################################
# Cloudflare Tunnel Auto Installer
# For aaPanel / Linux Server
# Author: Claude AI
# Version: 1.0
#############################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_header() {
    echo -e "${BLUE}"
    echo "============================================"
    echo "  Cloudflare Tunnel Auto Installer"
    echo "  For aaPanel / Linux Server"
    echo "============================================"
    echo -e "${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Script ini harus dijalankan sebagai root"
        echo "Gunakan: sudo bash install-cloudflare-tunnel.sh"
        exit 1
    fi
    print_success "Running as root"
}

detect_architecture() {
    ARCH=$(uname -m)
    case $ARCH in
        x86_64)
            CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64"
            print_success "Detected architecture: x86_64 (amd64)"
            ;;
        aarch64|arm64)
            CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64"
            print_success "Detected architecture: ARM64"
            ;;
        armv7l)
            CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm"
            print_success "Detected architecture: ARM"
            ;;
        *)
            print_error "Unsupported architecture: $ARCH"
            exit 1
            ;;
    esac
}

check_dependencies() {
    print_info "Checking dependencies..."

    # Check wget or curl
    if ! command -v wget &> /dev/null && ! command -v curl &> /dev/null; then
        print_warning "wget/curl not found, installing wget..."
        if command -v apt-get &> /dev/null; then
            apt-get update && apt-get install -y wget
        elif command -v yum &> /dev/null; then
            yum install -y wget
        else
            print_error "Cannot install wget. Please install manually."
            exit 1
        fi
    fi
    print_success "Dependencies OK"
}

download_cloudflared() {
    print_info "Downloading cloudflared..."

    cd /tmp

    if command -v wget &> /dev/null; then
        wget -q --show-progress "$CLOUDFLARED_URL" -O cloudflared
    else
        curl -L "$CLOUDFLARED_URL" -o cloudflared
    fi

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

    # Verify installation
    if cloudflared --version &> /dev/null; then
        VERSION=$(cloudflared --version | head -n1)
        print_success "Installed: $VERSION"
    else
        print_error "Installation failed"
        exit 1
    fi
}

cloudflare_login() {
    print_info "Cloudflare Login Process"
    echo ""
    print_warning "Browser akan terbuka untuk login ke Cloudflare"
    print_warning "Jika browser tidak terbuka, copy URL yang muncul ke browser"
    echo ""
    read -p "Press Enter to continue..."

    cloudflared tunnel login

    if [ -f ~/.cloudflared/cert.pem ]; then
        print_success "Login successful! Certificate saved."
    else
        print_error "Login failed. Certificate not found."
        exit 1
    fi
}

create_tunnel() {
    echo ""
    print_info "Creating Cloudflare Tunnel"
    echo ""

    # Input tunnel name
    read -p "Enter tunnel name (e.g., billing-dasnet): " TUNNEL_NAME

    if [ -z "$TUNNEL_NAME" ]; then
        print_error "Tunnel name cannot be empty"
        exit 1
    fi

    # Create tunnel
    print_info "Creating tunnel: $TUNNEL_NAME"
    TUNNEL_OUTPUT=$(cloudflared tunnel create "$TUNNEL_NAME" 2>&1)

    # Extract tunnel ID
    TUNNEL_ID=$(echo "$TUNNEL_OUTPUT" | grep -oP 'Created tunnel \K[a-f0-9-]+' | head -1)

    if [ -z "$TUNNEL_ID" ]; then
        print_error "Failed to create tunnel"
        echo "$TUNNEL_OUTPUT"
        exit 1
    fi

    print_success "Tunnel created successfully!"
    print_info "Tunnel Name: $TUNNEL_NAME"
    print_info "Tunnel ID: $TUNNEL_ID"

    # Save to file for later use
    echo "TUNNEL_NAME=$TUNNEL_NAME" > /root/.cloudflared/tunnel_info.txt
    echo "TUNNEL_ID=$TUNNEL_ID" >> /root/.cloudflared/tunnel_info.txt
}

configure_tunnel() {
    echo ""
    print_info "Configuring Tunnel"
    echo ""

    # Input domain
    read -p "Enter your domain (e.g., billing.yourdomain.com): " DOMAIN

    if [ -z "$DOMAIN" ]; then
        print_error "Domain cannot be empty"
        exit 1
    fi

    # Input port
    read -p "Enter website port in aaPanel (default: 80): " PORT
    PORT=${PORT:-80}

    # Create config directory
    mkdir -p ~/.cloudflared

    # Create config file
    cat > ~/.cloudflared/config.yml <<EOF
tunnel: $TUNNEL_ID
credentials-file: /root/.cloudflared/$TUNNEL_ID.json

ingress:
  - hostname: $DOMAIN
    service: http://localhost:$PORT
    originRequest:
      noTLSVerify: true
  - service: http_status:404
EOF

    print_success "Configuration file created: ~/.cloudflared/config.yml"

    # Save domain info
    echo "DOMAIN=$DOMAIN" >> /root/.cloudflared/tunnel_info.txt
    echo "PORT=$PORT" >> /root/.cloudflared/tunnel_info.txt
}

route_dns() {
    print_info "Routing DNS to tunnel..."

    cloudflared tunnel route dns "$TUNNEL_NAME" "$DOMAIN"

    print_success "DNS routed successfully!"
    print_info "Domain $DOMAIN now points to your tunnel"
}

install_service() {
    print_info "Installing cloudflared as system service..."

    # Install service
    cloudflared service install

    # Start service
    systemctl start cloudflared

    # Enable auto-start
    systemctl enable cloudflared

    # Check status
    sleep 2
    if systemctl is-active --quiet cloudflared; then
        print_success "Service installed and running!"
    else
        print_warning "Service installed but not running. Check logs with: journalctl -u cloudflared -f"
    fi
}

show_summary() {
    echo ""
    echo -e "${GREEN}"
    echo "============================================"
    echo "  Installation Complete!"
    echo "============================================"
    echo -e "${NC}"
    echo ""
    print_info "Tunnel Information:"
    echo "  - Tunnel Name: $TUNNEL_NAME"
    echo "  - Tunnel ID: $TUNNEL_ID"
    echo "  - Domain: $DOMAIN"
    echo "  - Local Port: $PORT"
    echo ""
    print_info "Configuration Files:"
    echo "  - Config: ~/.cloudflared/config.yml"
    echo "  - Credentials: ~/.cloudflared/$TUNNEL_ID.json"
    echo "  - Info: ~/.cloudflared/tunnel_info.txt"
    echo ""
    print_info "Service Management:"
    echo "  - Status: systemctl status cloudflared"
    echo "  - Start: systemctl start cloudflared"
    echo "  - Stop: systemctl stop cloudflared"
    echo "  - Restart: systemctl restart cloudflared"
    echo "  - Logs: journalctl -u cloudflared -f"
    echo ""
    print_info "Tunnel Management:"
    echo "  - List tunnels: cloudflared tunnel list"
    echo "  - Delete tunnel: cloudflared tunnel delete $TUNNEL_NAME"
    echo ""
    print_success "Your website should now be accessible at: https://$DOMAIN"
    echo ""
    print_warning "Note: DNS propagation may take a few minutes"
    echo ""
}

test_connection() {
    echo ""
    read -p "Do you want to test the connection? (y/n): " TEST_CONN

    if [ "$TEST_CONN" = "y" ] || [ "$TEST_CONN" = "Y" ]; then
        print_info "Testing local connection..."

        if curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT | grep -q "200\|301\|302"; then
            print_success "Local website is responding"
        else
            print_warning "Local website may not be running on port $PORT"
            print_info "Please check your aaPanel website configuration"
        fi

        print_info "Testing tunnel connection..."
        sleep 3

        if curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN | grep -q "200\|301\|302"; then
            print_success "Tunnel is working! Website is accessible"
        else
            print_warning "Tunnel may still be initializing. Wait a few minutes and try again."
        fi
    fi
}

# Main Installation Process
main() {
    print_header

    check_root
    detect_architecture
    check_dependencies
    download_cloudflared
    install_cloudflared

    echo ""
    print_info "Cloudflared installed successfully!"
    echo ""

    read -p "Do you want to configure tunnel now? (y/n): " CONFIGURE

    if [ "$CONFIGURE" = "y" ] || [ "$CONFIGURE" = "Y" ]; then
        cloudflare_login
        create_tunnel
        configure_tunnel
        route_dns
        install_service
        show_summary
        test_connection
    else
        print_info "Cloudflared installed. You can configure it later with:"
        echo "  cloudflared tunnel login"
        echo "  cloudflared tunnel create <name>"
    fi

    echo ""
    print_success "Installation script completed!"
    echo ""
}

# Run main function
main
