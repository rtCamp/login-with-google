#!/usr/bin/env bash

# Exit immediately if a command fails
set -e

# Ensure the script is executed from the project root
SCRIPT_DIR=$(dirname "$0")
cd "$SCRIPT_DIR/.." || exit 1

# Define environment variables
PLUGIN_SLUG="login-with-google"

# Extract version from readme.txt
VERSION=$(grep -m1 "Stable tag:" readme.txt | awk '{print $NF}')
if [[ -z "$VERSION" ]]; then
    echo "Error: Unable to determine the version from readme.txt"
    exit 1
fi

# Store the root directory path
ROOT_DIR=$(pwd)
RELEASE_DIR="${ROOT_DIR}/release"
TEMP_DIR="${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}-temp"
FINAL_DIR="${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}"
ZIP_FILE="${RELEASE_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"

# Ensure the release directory exists
mkdir -p "$RELEASE_DIR"

# Copy files to the temp directory, excluding unwanted files
echo "Copying project files to temporary release directory..."
rsync -av --exclude='temp' --exclude='assets/node_modules' --exclude='vendor' --exclude='.git' --exclude='.idea' ./ "$TEMP_DIR/"

# Change to temp directory
cd "$TEMP_DIR" || exit 1

# Ensure PHP is installed
echo "Checking PHP installation..."
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed. Please install PHP 7.4 or higher."
    exit 1
fi

# Run build assets script
echo "Running build-assets script..."
if [[ -x "./bin/build-assets.sh" ]]; then
    ./bin/build-assets.sh
else
    echo "Error: build-assets.sh not found or not executable"
    exit 1
fi

# Create the final release directory and copy files using .distignore
echo "Creating final release directory..."
cd "$RELEASE_DIR" || exit 1
mkdir -p "$FINAL_DIR"

rsync -av --exclude-from="$ROOT_DIR/.distignore" "$TEMP_DIR/" "$FINAL_DIR/"

# Create release zip
echo "Creating release zip..."
zip -r "$ZIP_FILE" "$(basename "$FINAL_DIR")"

# Clean up temporary directories
echo "Cleaning up..."
rm -rf "$TEMP_DIR"
rm -rf "$FINAL_DIR"

echo "✅ Release zip created at: $ZIP_FILE"
