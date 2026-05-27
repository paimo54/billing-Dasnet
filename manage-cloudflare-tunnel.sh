#!/bin/bash

#############################################
# Cloudflare Tunnel Management Script
# For aaPanel / Linux Server
# Author: Claude AI
# Version: 1.0
#############################################

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
    echo "║   Cloudflare Tunnel Management Panel      ║"
    echo "║   For aaPanel / Linux Server              ║"
    echo "╚════════════════════════════════════════════╝"
    echo -e "${NC}"
}

check_installed() {
    if ! command -v cloudflared &> /dev/null; then
        print_error "Cloudflared is not installed!"
        echo ""
        echo "Please install first using:"
        echo "  bash install-cloudflare-tunnel.sh"
        exit 1
    fi
}

show_menu() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo -e "${GREEN}  Main Menu${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo ""
    echo "  1) Show Tunnel Status"
    echo "  2) List All Tunnels"
    echo "  3) Show Tunnel Info"
    echo "  4) View Service Logs"
    echo "  5) Start Service"
    echo "  6) Stop Service"
    echo "  7) Restart Service"
    echo "  8) Create New Tunnel"
    echo "  9) Delete Tunnel"
    echo " 10) Update Configuration"
    echo " 11) Test Connection"
    echo " 12) Show Configuration"
    echo "  0) Exit"
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════${NC}"
    echo ""
}

show_status() {
    print_info "Cloudflared Service Status:"
    echo ""
    systemctl status cloudflared --no-pager -l
    echo ""
    read -p "Press Enter to continue..."
}

list_tunnels() {
    print_info "List of Tunnels:"
    echo ""
    cloudflared tunnel list
    echo ""
    read -p "Press Enter to continue..."
}

show_tunnel_info() {
    if [ -f ~/.cloudflared/tunnel_info.txt ]; then
        print_info "Current Tunnel Information:"
        echo ""
        cat ~/.cloudflared/tunnel_info.txt
        echo ""
    else
        print_warning "No tunnel info file found"
    fi
    read -p "Press Enter to continue..."
}

view_logs() {
    print_info "Viewing service logs (Press Ctrl+C to exit)..."
    echo ""
    sleep 2
    journalctl -u cloudflared -f
}

start_service() {
    print_info "Starting cloudflared service..."
    systemctl start cloudflared
    sleep 2
    if systemctl is-active --quiet cloudflared; then
        print_success "Service started successfully!"
    else
        print_error "Failed to start service"
    fi
    echo ""
    read -p "Press Enter to continue..."
}

stop_service() {
    print_info "Stopping cloudflared service..."
    systemctl stop cloudflared
    sleep 2
    if ! systemctl is-active --quiet cloudflared; then
        print_success "Service stopped successfully!"
    else
        print_error "Failed to stop service"
    fi
    echo ""
    read -p "Press Enter to continue..."
}

restart_service() {
    print_info "Restarting cloudflared service..."
    systemctl restart cloudflared
    sleep 2
    if systemctl is-active --quiet cloudflared; then
        print_success "Service restarted successfully!"
    else
        print_error "Failed to restart service"
    fi
    echo ""
    read -p "Press Enter to continue..."
}

create_tunnel() {
    echo ""
    print_info "Create New Tunnel"
    echo ""

    read -p "Enter tunnel name: " TUNNEL_NAME

    if [ -z "$TUNNEL_NAME" ]; then
        print_error "Tunnel name cannot be empty"
        read -p "Press Enter to continue..."
        return
    fi

    print_info "Creating tunnel: $TUNNEL_NAME"
    cloudflared tunnel create "$TUNNEL_NAME"

    print_success "Tunnel created!"
    echo ""
    print_warning "Don't forget to:"
    echo "  1. Update configuration (option 10)"
    echo "  2. Route DNS to tunnel"
    echo "  3. Restart service (option 7)"
    echo ""
    read -p "Press Enter to continue..."
}

delete_tunnel() {
    echo ""
    print_info "Delete Tunnel"
    echo ""

    cloudflared tunnel list
    echo ""

    read -p "Enter tunnel name or ID to delete: " TUNNEL_ID

    if [ -z "$TUNNEL_ID" ]; then
        print_error "Tunnel name/ID cannot be empty"
        read -p "Press Enter to continue..."
        return
    fi

    read -p "Are you sure you want to delete tunnel '$TUNNEL_ID'? (yes/no): " CONFIRM

    if [ "$CONFIRM" = "yes" ]; then
        cloudflared tunnel delete "$TUNNEL_ID" -f
        print_success "Tunnel deleted!"
    else
        print_info "Deletion cancelled"
    fi

    echo ""
    read -p "Press Enter to continue..."
}

update_config() {
    echo ""
    print_info "Update Configuration"
    echo ""

    if [ ! -f ~/.cloudflared/config.yml ]; then
        print_error "Configuration file not found!"
        read -p "Press Enter to continue..."
        return
    fi

    print_info "Current configuration:"
    echo ""
    cat ~/.cloudflared/config.yml
    echo ""

    read -p "Do you want to edit the configuration? (y/n): " EDIT

    if [ "$EDIT" = "y" ] || [ "$EDIT" = "Y" ]; then
        if command -v nano &> /dev/null; then
            nano ~/.cloudflared/config.yml
        elif command -v vi &> /dev/null; then
            vi ~/.cloudflared/config.yml
        else
            print_error "No text editor found (nano/vi)"
        fi

        print_success "Configuration updated!"
        print_warning "Restart service to apply changes (option 7)"
    fi

    echo ""
    read -p "Press Enter to continue..."
}

test_connection() {
    echo ""
    print_info "Testing Connection"
    echo ""

    if [ -f ~/.cloudflared/tunnel_info.txt ]; then
        source ~/.cloudflared/tunnel_info.txt

        print_info "Testing local port $PORT..."
        if curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT | grep -q "200\|301\|302"; then
            print_success "Local website is responding on port $PORT"
        else
            print_error "Local website is not responding on port $PORT"
        fi

        echo ""
        print_info "Testing domain $DOMAIN..."
        if curl -s -o /dev/null -w "%{http_code}" https://$DOMAIN | grep -q "200\|301\|302"; then
            print_success "Domain $DOMAIN is accessible"
        else
            print_error "Domain $DOMAIN is not accessible"
        fi
    else
        print_warning "No tunnel info found. Testing manually..."
        echo ""
        read -p "Enter domain to test: " TEST_DOMAIN
        read -p "Enter local port to test: " TEST_PORT

        print_info "Testing local port $TEST_PORT..."
        curl -I http://localhost:$TEST_PORT

        echo ""
        print_info "Testing domain $TEST_DOMAIN..."
        curl -I https://$TEST_DOMAIN
    fi

    echo ""
    read -p "Press Enter to continue..."
}

show_config() {
    echo ""
    print_info "Current Configuration"
    echo ""

    if [ -f ~/.cloudflared/config.yml ]; then
        cat ~/.cloudflared/config.yml
    else
        print_error "Configuration file not found!"
    fi

    echo ""
    read -p "Press Enter to continue..."
}

main() {
    check_installed

    while true; do
        print_header
        show_menu
        read -p "Select option [0-12]: " choice

        case $choice in
            1) show_status ;;
            2) list_tunnels ;;
            3) show_tunnel_info ;;
            4) view_logs ;;
            5) start_service ;;
            6) stop_service ;;
            7) restart_service ;;
            8) create_tunnel ;;
            9) delete_tunnel ;;
            10) update_config ;;
            11) test_connection ;;
            12) show_config ;;
            0)
                echo ""
                print_success "Goodbye!"
                echo ""
                exit 0
                ;;
            *)
                print_error "Invalid option!"
                sleep 2
                ;;
        esac
    done
}

main
