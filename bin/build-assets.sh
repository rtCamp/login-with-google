#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

# Run Composer install without dev dependencies
echo "Installing PHP dependencies..."
composer install --no-dev

# Use the correct Node.js version if NVM is available
if command -v nvm &> /dev/null; then
    echo "Using NVM to set Node.js version..."
    nvm use
else
    echo "NVM is not installed. Skipping NVM setup."
fi

# Navigate to the assets directory
cd ./assets || { echo "Error: ./assets directory not found"; exit 1; }

echo "Installing dependencies..."
npm install

echo "Building assets..."
npm run production

echo "Cleaning up node_modules..."
rm -rf node_modules

echo "Build completed successfully."
