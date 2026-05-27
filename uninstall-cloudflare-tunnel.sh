#!/bin/bash

#############################################
# Cloudflare Tunnel Uninstaller
# For aaPanel / Linux Server
# Author: Claude AI
# Version: 1.0
#############################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }

print_header() {
    echo -e "${RED}"
    echo "============================================"
    echo "  Cloudflare Tunnel Uninstaller"
    echo "============================================"
    echo -e "${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Script ini harus dijalankan sebagai root"
        exit 1
    fi
}

confirm_uninstall() {
    echo ""
    print_warning "This will remove Cloudflare Tunnel completely!"
    print_warning "All tunnels and configurations will be deleted."
    echo ""
    read -p "Are you sure you want to continue? (yes/no): " CONFIRM

    if [ "$CONFIRM" != "yes" ]; then
        print_info "Uninstall cancelled"
        exit 0
    fi
}

stop_service() {
    print_info "Stopping cloudflared service..."

    if systemctl is-active --quiet cloudflared; then
        systemctl stop cloudflared
        print_success "Service stopped"
    else
        print_info "Service is not running"
    fi
}

disable_service() {
    print_info "Disabling cloudflared service..."

    if systemctl is-enabled --quiet cloudflared 2>/dev/null; then
        systemctl disable cloudflared
        print_success "Service disabled"
    else
        print_info "Service is not enabled"
    fi
}

uninstall_service() {
    print_info "Uninstalling cloudflared service..."

    if [ -f /etc/systemd/system/cloudflared.service ]; then
        cloudflared service uninstall 2>/dev/null || true
        systemctl daemon-reload
        print_success "Service uninstalled"
    else
        print_info "Service not found"
    fi
}

delete_tunnels() {
    print_info "Deleting tunnels..."

    if command -v cloudflared &> /dev/null; then
        TUNNELS=$(cloudflared tunnel list 2>/dev/null | grep -v "NAME" | awk '{print $1}' || true)

        if [ -n "$TUNNELS" ]; then
            echo "$TUNNELS" | while read -r tunnel_id; do
                if [ -n "$tunnel_id" ]; then
                    print_info "Deleting tunnel: $tunnel_id"
                    cloudflared tunnel delete "$tunnel_id" -f 2>/dev/null || true
                fi
            done
            print_success "Tunnels deleted"
        else
            print_info "No tunnels found"
        fi
    fi
}

remove_binary() {
    print_info "Removing cloudflared binary..."

    if [ -f /usr/local/bin/cloudflared ]; then
        rm -f /usr/local/bin/cloudflared
        print_success "Binary removed"
    else
        print_info "Binary not found"
    fi
}

remove_config() {
    print_info "Removing configuration files..."

    if [ -d ~/.cloudflared ]; then
        rm -rf ~/.cloudflared
        print_success "Configuration removed"
    else
        print_info "Configuration not found"
    fi
}

show_summary() {
    echo ""
    echo -e "${GREEN}"
    echo "============================================"
    echo "  Uninstall Complete!"
    echo "============================================"
    echo -e "${NC}"
    echo ""
    print_success "Cloudflare Tunnel has been completely removed"
    echo ""
}

main() {
    print_header
    check_root
    confirm_uninstall

    echo ""
    stop_service
    disable_service
    uninstall_service
    delete_tunnels
    remove_binary
    remove_config

    show_summary
}

main
