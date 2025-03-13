#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

# Ensure the script is executed from the project root
dirname=$(dirname "$0")
cd "$dirname/.." || exit 1

# Create a temp directory
mkdir "temp"

# Copy everything from current directory to temp directory, exclude temp and files from .distignore
rsync -av --exclude='temp' --exclude='assets/node_modules' --exclude='vendor' --exclude='.git' --exclude='.idea' ./ temp/

# store the root directory path
ROOT_DIR=$(pwd)

# Change to temp directory
cd temp || exit 1

# Define environment variables
PLUGIN_SLUG="login-with-google"

# Extract version from readme.txt
VERSION=$(grep -m1 "Stable tag:" readme.txt | awk '{print $NF}')

# Set up PHP
echo "Setting up PHP..."
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP 7.4 or higher."
    exit 1
fi

# Run build assets script
./bin/build-assets.sh

# Create release zip using .distignore for exclusions
echo "Creating release zip..."
mkdir -p ../release
rsync -av --exclude-from=".distignore" ./ ../release/${PLUGIN_SLUG}-${VERSION}/
cd ../release
zip -r "${PLUGIN_SLUG}-${VERSION}.zip" "${PLUGIN_SLUG}-${VERSION}"
rm -rf "${PLUGIN_SLUG}-${VERSION}"

# Clean up
rm -rf "${ROOT_DIR}/temp"

echo "Release zip created at ./release/${PLUGIN_SLUG}-${VERSION}.zip"
