#!/bin/bash
#
# Setup swap file for t3.micro instances
# Run this script on your EC2 instance before building Docker images
#

set -e

SWAP_SIZE=${1:-2G}
SWAP_FILE=/swapfile

echo "==================================================================="
echo "Setting up ${SWAP_SIZE} swap file on EC2 t3.micro instance"
echo "==================================================================="

# Check if swap already exists
if [ -f "$SWAP_FILE" ]; then
    echo "⚠️  Swap file already exists at $SWAP_FILE"
    echo "Current swap status:"
    free -h
    echo ""
    read -p "Do you want to recreate it? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Exiting without changes."
        exit 0
    fi
    
    echo "Disabling existing swap..."
    sudo swapoff $SWAP_FILE
    sudo rm $SWAP_FILE
fi

# Create swap file
echo "Creating ${SWAP_SIZE} swap file..."
sudo fallocate -l $SWAP_SIZE $SWAP_FILE

# Set permissions
echo "Setting permissions..."
sudo chmod 600 $SWAP_FILE

# Make it a swap file
echo "Formatting as swap..."
sudo mkswap $SWAP_FILE

# Enable swap
echo "Enabling swap..."
sudo swapon $SWAP_FILE

# Verify
echo ""
echo "✅ Swap enabled successfully!"
echo ""
echo "Current memory status:"
free -h

# Make permanent
if ! grep -q "$SWAP_FILE" /etc/fstab; then
    echo ""
    read -p "Make swap permanent across reboots? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        echo "$SWAP_FILE none swap sw 0 0" | sudo tee -a /etc/fstab
        echo "✅ Swap added to /etc/fstab (permanent)"
    fi
fi

echo ""
echo "==================================================================="
echo "Setup complete! You can now build Docker images."
echo "==================================================================="
