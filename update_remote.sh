#!/bin/bash
# Script to update Git remote to your forked repository
# Run this ONLY if you fork the repository

echo "This script will update your Git remote to point to your forked repository"
echo "Current remote URL: $(git remote get-url origin)"
echo ""
echo "BEFORE running this script:"
echo "1. Go to https://github.com/viswaa3345/SmartQueue"
echo "2. Click 'Fork' button to create your own fork"
echo "3. Wait for fork to be created"
echo ""

read -p "Have you forked the repository? (y/N): " response
if [[ "$response" =~ ^[Yy]$ ]]; then
    echo "Updating remote URL to your fork..."
    git remote set-url origin https://github.com/subashshanmugmam/SmartQueue.git
    echo "Remote URL updated to: $(git remote get-url origin)"
    echo ""
    echo "Now you can push:"
    echo "git push origin main"
else
    echo "Please fork the repository first, then run this script again."
fi