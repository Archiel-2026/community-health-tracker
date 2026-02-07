@echo off
REM Tailwind CSS Build Script for Community Health Tracker
REM This script rebuilds the Tailwind CSS file for offline use

cd /d %~dp0

echo.
echo ========================================
echo   Community Health Tracker
echo   Tailwind CSS Build Script
echo ========================================
echo.

REM Check if npm is installed
where npm >nul 2>nul
if errorlevel 1 (
    echo ERROR: npm is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/
    pause
    exit /b 1
)

echo Checking Node.js and npm versions...
node --version
npm --version
echo.

REM Check if package.json exists
if not exist "package.json" (
    echo ERROR: package.json not found in current directory
    echo Please run this script from the project root directory
    pause
    exit /b 1
)

REM Check if node_modules exists, if not install dependencies
if not exist "node_modules" (
    echo Installing dependencies...
    call npm install
    if errorlevel 1 (
        echo ERROR: npm install failed
        pause
        exit /b 1
    )
    echo Dependencies installed successfully!
    echo.
)

REM Build Tailwind CSS
echo Building Tailwind CSS...
echo.
call npm run build:css

if errorlevel 1 (
    echo ERROR: Tailwind CSS build failed
    pause
    exit /b 1
)

echo.
echo ========================================
echo   ✓ Tailwind CSS built successfully!
echo ========================================
echo.
echo Build output: asssets/css/tailwind.css
echo.
echo To watch for changes during development:
echo   npm run watch:css
echo.
pause
