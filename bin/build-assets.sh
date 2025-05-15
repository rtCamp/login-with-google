#!/usr/bin/env bash

# Exit immediately if a command fails
set -e

echo "🚀 Starting build process..."

# Ensure Composer is installed
if ! command -v composer &>/dev/null; then
    echo "❌ Error: Composer is not installed. Please install Composer before proceeding."
    exit 1
fi

# Run Composer install without dev dependencies
echo "📦 Installing PHP dependencies (without dev)..."
composer install --no-dev --prefer-dist --optimize-autoloader

# Ensure npm is installed
if ! command -v npm &>/dev/null; then
    echo "❌ Error: npm is not installed. Please install Node.js and npm before proceeding."
    exit 1
fi

echo "📦 Installing Node.js dependencies..."
npm install --silent

echo "⚡ Building assets..."
if ! npm run production; then
    echo "❌ Error: Asset build failed."
    exit 1
fi

# Cleanup unnecessary files
echo "🧹 Cleaning up node_modules..."
rm -rf node_modules package-lock.json

echo "✅ Build completed successfully!"
