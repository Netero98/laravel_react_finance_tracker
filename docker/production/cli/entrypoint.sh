#!/bin/bash
set -e

echo "➡️ Starting installing npm packages: npm ci"
npm ci
echo "✅ npm packages installed!"

echo "➡️ Running frontend build..."
npm run build
echo "✅ Build complete. Starting idle container..."

# Поддерживаем контейнер живым
tail -f /dev/null
