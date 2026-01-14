#!/bin/bash
# PHASE B PILOT - Deployment Script
# Automates deployment of Identity + Camat integration
# Version: 1.0.0
# Date: 2026-01-10

set -e  # Exit on error

echo "========================================="
echo "PHASE B PILOT - Deployment Script"
echo "Identity Module + Camat Integration"
echo "========================================="
echo ""

# Configuration
KANTOR_ROOT="/var/www/html/kantor"
BACKUP_DIR="/var/backups/kantor"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
function info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

function warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

function error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    warn "Not running as root. You may need sudo for some operations."
fi

# Step 1: Verify we're in the right directory
if [ ! -f "PHASE_B_DEPLOY_README.md" ]; then
    error "PHASE_B_DEPLOY_README.md not found. Are you in the deployment package directory?"
fi

info "Deployment package verified ✓"

# Step 2: Create backup directory
mkdir -p "$BACKUP_DIR"
info "Backup directory ready: $BACKUP_DIR"

# Step 3: Backup current state
info "Creating backup..."
if [ -d "$KANTOR_ROOT/camat" ] && [ -d "$KANTOR_ROOT/id" ]; then
    tar -czf "$BACKUP_DIR/backup_pre_phase_b_$TIMESTAMP.tar.gz" \
        -C "$KANTOR_ROOT" \
        camat/ id/ 2>/dev/null || warn "Some files couldn't be backed up (may not exist yet)"
    info "Backup created: backup_pre_phase_b_$TIMESTAMP.tar.gz ✓"
else
    warn "Camat or ID directory not found. Skipping backup."
fi

# Step 4: Check target directories exist
info "Checking target directories..."
if [ ! -d "$KANTOR_ROOT" ]; then
    error "Kantor root not found: $KANTOR_ROOT"
fi

if [ ! -d "$KANTOR_ROOT/id" ]; then
    warn "ID directory not found, creating..."
    mkdir -p "$KANTOR_ROOT/id"
fi

if [ ! -d "$KANTOR_ROOT/camat" ]; then
    error "Camat directory not found: $KANTOR_ROOT/camat"
fi

# Step 5: Deploy Identity UI Gateway
info "Deploying Identity UI Gateway..."
cp -r id/auth "$KANTOR_ROOT/id/"
chmod 755 "$KANTOR_ROOT/id/auth"
chmod 644 "$KANTOR_ROOT/id/auth"/*.php
info "Identity UI Gateway deployed ✓"

# Step 6: Deploy Camat files
info "Deploying Camat integration files..."

# Copy auth directory
cp -r camat/auth "$KANTOR_ROOT/camat/"
chmod 755 "$KANTOR_ROOT/camat/auth"
chmod 644 "$KANTOR_ROOT/camat/auth"/*.php

# Copy login_identity.php
cp camat/login_identity.php "$KANTOR_ROOT/camat/"
chmod 644 "$KANTOR_ROOT/camat/login_identity.php"

# Copy docs
cp camat/PHASE_B_INTERNAL_DOCS.md "$KANTOR_ROOT/camat/"
chmod 644 "$KANTOR_ROOT/camat/PHASE_B_INTERNAL_DOCS.md"

# Update auth.php
cp camat/includes/auth.php "$KANTOR_ROOT/camat/includes/"
chmod 644 "$KANTOR_ROOT/camat/includes/auth.php"

info "Camat files deployed ✓"

# Step 7: Create log directory
info "Setting up log directory..."
mkdir -p "$KANTOR_ROOT/camat/logs"
chmod 755 "$KANTOR_ROOT/camat/logs"
touch "$KANTOR_ROOT/camat/logs/identity_audit.log"
chmod 666 "$KANTOR_ROOT/camat/logs/identity_audit.log"
info "Log directory ready ✓"

# Step 8: Verify deployment
info "Verifying deployment..."

ERRORS=0

if [ ! -f "$KANTOR_ROOT/id/auth/login.php" ]; then
    error "Identity login.php not found!"
    ERRORS=$((ERRORS + 1))
fi

if [ ! -f "$KANTOR_ROOT/camat/auth/callback.php" ]; then
    error "Camat callback.php not found!"
    ERRORS=$((ERRORS + 1))
fi

if [ ! -f "$KANTOR_ROOT/camat/login_identity.php" ]; then
    error "Camat login_identity.php not found!"
    ERRORS=$((ERRORS + 1))
fi

if [ ! -d "$KANTOR_ROOT/camat/logs" ]; then
    error "Log directory not created!"
    ERRORS=$((ERRORS + 1))
fi

if [ $ERRORS -gt 0 ]; then
    error "Deployment verification failed with $ERRORS errors"
else
    info "All files deployed successfully ✓"
fi

# Step 9: Set ownership (if needed)
if [ -n "$SUDO_USER" ]; then
    info "Setting file ownership to www-data..."
    chown -R www-data:www-data "$KANTOR_ROOT/id/auth"
    chown -R www-data:www-data "$KANTOR_ROOT/camat/auth"
    chown -R www-data:www-data "$KANTOR_ROOT/camat/logs"
    chown www-data:www-data "$KANTOR_ROOT/camat/login_identity.php"
    info "Ownership set ✓"
fi

echo ""
echo "========================================="
echo -e "${GREEN}DEPLOYMENT SUCCESSFUL!${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Test Identity UI:"
echo "   curl https://id.sidiksae.my.id/auth/login.php?app=camat"
echo ""
echo "2. Test Camat login:"
echo "   Visit: https://camat.sidiksae.my.id/login_identity.php"
echo ""
echo "3. Monitor audit log:"
echo "   tail -f $KANTOR_ROOT/camat/logs/identity_audit.log"
echo ""
echo "Backup location: $BACKUP_DIR/backup_pre_phase_b_$TIMESTAMP.tar.gz"
echo ""
