#!/bin/bash

#############################################
# Quick Deploy Script for aaPanel
# Upload this file and run: bash quick-deploy.sh
#############################################

echo "=========================================="
echo "  Billing System - Quick Deploy"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root: sudo bash quick-deploy.sh"
    exit 1
fi

# Get current directory
CURRENT_DIR=$(pwd)

# Check if install.sh exists
if [ ! -f "$CURRENT_DIR/install.sh" ]; then
    echo "Error: install.sh not found in current directory"
    echo "Please make sure all files are uploaded to the same directory"
    exit 1
fi

# Make install.sh executable
chmod +x "$CURRENT_DIR/install.sh"

# Run installer
echo "Starting installation..."
echo ""
bash "$CURRENT_DIR/install.sh"
