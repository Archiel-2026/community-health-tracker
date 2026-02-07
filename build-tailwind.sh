#!/bin/bash

# Tailwind CSS Build Script for Community Health Tracker
# This script rebuilds the Tailwind CSS file for offline use

echo ""
echo "========================================"
echo "  Community Health Tracker"
echo "  Tailwind CSS Build Script"
echo "========================================"
echo ""

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "ERROR: npm is not installed"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi

echo "Checking Node.js and npm versions..."
node --version
npm --version
echo ""

# Check if package.json exists
if [ ! -f "package.json" ]; then
    echo "ERROR: package.json not found in current directory"
    echo "Please run this script from the project root directory"
    exit 1
fi

# Check if node_modules exists, if not install dependencies
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
    if [ $? -ne 0 ]; then
        echo "ERROR: npm install failed"
        exit 1
    fi
    echo "Dependencies installed successfully!"
    echo ""
fi

# Build Tailwind CSS
echo "Building Tailwind CSS..."
echo ""
npm run build:css

if [ $? -ne 0 ]; then
    echo "ERROR: Tailwind CSS build failed"
    exit 1
fi

echo ""
echo "========================================"
echo "  ✓ Tailwind CSS built successfully!"
echo "========================================"
echo ""
echo "Build output: asssets/css/tailwind.css"
echo ""
echo "To watch for changes during development:"
echo "  npm run watch:css"
echo ""
