#!/bin/bash

# Script to build a WordPress plugin zip file for the EIA Fuel Surcharge plugin
# Author: Claude
# Usage: ./build-plugin.sh [version]

# Set script to exit on error, but allow commands with non-zero exit codes in certain places
set -e

# Get version from argument or use default
VERSION=${1:-"1.0.0"}
PLUGIN_SLUG="eia-fuel-surcharge"
PLUGIN_NAME="EIA Fuel Surcharge Display"
BUILD_DIR="./build"
DIST_DIR="./dist"
PLUGIN_DIR="${BUILD_DIR}/${PLUGIN_SLUG}"

# Display banner
echo "=========================================================="
echo "Building ${PLUGIN_NAME} v${VERSION}"
echo "=========================================================="

# Create build directory if it doesn't exist
if [ ! -d "${BUILD_DIR}" ]; then
  echo "Creating build directory..."
  mkdir -p "${BUILD_DIR}"
fi

# Create distribution directory if it doesn't exist
if [ ! -d "${DIST_DIR}" ]; then
  echo "Creating distribution directory..."
  mkdir -p "${DIST_DIR}"
fi

# Remove existing plugin build directory if it exists
if [ -d "${PLUGIN_DIR}" ]; then
  echo "Removing existing build files..."
  rm -rf "${PLUGIN_DIR}"
fi

# Create plugin directory
echo "Creating plugin directory structure..."
mkdir -p "${PLUGIN_DIR}"
mkdir -p "${PLUGIN_DIR}/assets/css"
mkdir -p "${PLUGIN_DIR}/assets/js"
mkdir -p "${PLUGIN_DIR}/includes/Admin"
mkdir -p "${PLUGIN_DIR}/includes/API"
mkdir -p "${PLUGIN_DIR}/includes/Core"
mkdir -p "${PLUGIN_DIR}/includes/Frontend"
mkdir -p "${PLUGIN_DIR}/includes/Utilities"
mkdir -p "${PLUGIN_DIR}/templates/admin"
mkdir -p "${PLUGIN_DIR}/templates/public"
mkdir -p "${PLUGIN_DIR}/languages"

# Copy plugin files
echo "Copying plugin files..."

# Main plugin files - use || true to prevent failure if file doesn't exist
cp eia-fuel-surcharge.php "${PLUGIN_DIR}/" || true
cp uninstall.php "${PLUGIN_DIR}/" || true
cp readme.txt "${PLUGIN_DIR}/" || true
[ -f "composer.json" ] && cp composer.json "${PLUGIN_DIR}/" || true
[ -d "languages" ] && cp -r languages/* "${PLUGIN_DIR}/languages/" 2>/dev/null || true

# Copy assets
echo "Copying assets..."
[ -d "assets/css" ] && cp -r assets/css/* "${PLUGIN_DIR}/assets/css/" 2>/dev/null || true
[ -d "assets/js" ] && cp -r assets/js/* "${PLUGIN_DIR}/assets/js/" 2>/dev/null || true

# Copy includes
echo "Copying includes..."
[ -d "includes/Admin" ] && cp -r includes/Admin/* "${PLUGIN_DIR}/includes/Admin/" 2>/dev/null || true
[ -d "includes/API" ] && cp -r includes/API/* "${PLUGIN_DIR}/includes/API/" 2>/dev/null || true
[ -d "includes/Core" ] && cp -r includes/Core/* "${PLUGIN_DIR}/includes/Core/" 2>/dev/null || true
[ -d "includes/Frontend" ] && cp -r includes/Frontend/* "${PLUGIN_DIR}/includes/Frontend/" 2>/dev/null || true
[ -d "includes/Utilities" ] && cp -r includes/Utilities/* "${PLUGIN_DIR}/includes/Utilities/" 2>/dev/null || true

# Copy templates
echo "Copying templates..."
[ -d "templates/admin" ] && cp -r templates/admin/* "${PLUGIN_DIR}/templates/admin/" 2>/dev/null || true
[ -d "templates/public" ] && cp -r templates/public/* "${PLUGIN_DIR}/templates/public/" 2>/dev/null || true

# Check if Composer is needed and available
if [ -f "${PLUGIN_DIR}/composer.json" ] && [ ! -d "${PLUGIN_DIR}/vendor" ]; then
  # Check if composer is installed and available
  if command -v composer &> /dev/null; then
    echo "Installing dependencies with Composer..."
    (cd "${PLUGIN_DIR}" && composer install --no-dev --optimize-autoloader) || {
      echo "Composer installation failed, but continuing build process..."
    }
  else
    echo "Composer not found, skipping dependency installation."
    echo "Note: Plugin may require manual installation of dependencies."
  fi
fi

# Update version in main plugin file if specified
if [ "${VERSION}" != "1.0.0" ] && [ -f "${PLUGIN_DIR}/eia-fuel-surcharge.php" ]; then
  echo "Updating version to ${VERSION}..."
  # Use different approach for macOS vs Linux
  if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/Version:           1.0.0/Version:           ${VERSION}/" "${PLUGIN_DIR}/eia-fuel-surcharge.php" || true
    sed -i '' "s/define('EIA_FUEL_SURCHARGE_VERSION', '1.0.0')/define('EIA_FUEL_SURCHARGE_VERSION', '${VERSION}')/" "${PLUGIN_DIR}/eia-fuel-surcharge.php" || true
  else
    # Linux or other
    sed -i "s/Version:           1.0.0/Version:           ${VERSION}/" "${PLUGIN_DIR}/eia-fuel-surcharge.php" || true
    sed -i "s/define('EIA_FUEL_SURCHARGE_VERSION', '1.0.0')/define('EIA_FUEL_SURCHARGE_VERSION', '${VERSION}')/" "${PLUGIN_DIR}/eia-fuel-surcharge.php" || true
  fi
fi

# Create the zip file
echo "Creating zip file..."
(
  cd "${BUILD_DIR}" || {
    echo "Unable to change to build directory."
    exit 1
  }
  
  ZIP_FILE="../${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
  
  # Remove existing zip file if it exists
  [ -f "${ZIP_FILE}" ] && rm "${ZIP_FILE}"
  
  # Create zip file
  if command -v zip &> /dev/null; then
    zip -r "${ZIP_FILE}" "${PLUGIN_SLUG}" || {
      echo "Zip creation failed. Please check if 'zip' is installed."
      exit 1
    }
  else
    echo "Error: 'zip' command not found. Please install it to create the plugin package."
    exit 1
  fi
)

# Check if zip file was created successfully
if [ -f "${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip" ]; then
  # Use du command if available, otherwise skip size display
  if command -v du &> /dev/null; then
    echo "✅ Successfully created: ${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
    echo "Plugin size: $(du -h "${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip" 2>/dev/null | cut -f1 2>/dev/null || echo "size calculation unavailable")"
  else
    echo "✅ Successfully created: ${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
  fi
else
  echo "❌ Error: Failed to create zip file"
  exit 1
fi

# Clean up build directory if needed
read -p "Do you want to remove the build files? (y/n): " -n 1 -r REPLY
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
  echo "Cleaning up build files..."
  rm -rf "${BUILD_DIR}"
  echo "Build files removed."
fi

echo "=========================================================="
echo "Build process completed!"
echo "The plugin zip file is located at: ${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
echo "=========================================================="