#!/bin/bash
set -e

# 安裝 pnpm
npm install -g pnpm@10.32.0

# 安裝 powerhouse composer 依賴（phpstan bootstrapFiles 需要）
echo "→ Installing powerhouse composer dependencies..."
cd /workspaces/powerrepo/apps/powerhouse
composer install --no-interaction

# 安裝 power-course composer 依賴（phpcs, phpstan 等）
echo "→ Installing power-course composer dependencies..."
cd /workspaces/powerrepo/apps/power-course
composer install --no-interaction

# 在 monorepo 根目錄安裝 JS 依賴（workspace 設定）
echo "→ Installing JS dependencies..."
cd /workspaces/powerrepo
pnpm install

echo "✓ Setup complete"
