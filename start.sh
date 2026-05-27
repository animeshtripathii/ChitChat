#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

echo "🚀 Starting ChitChat Deployment Bootstrapping..."

# 1. Warm up Laravel configuration, routing, and view caches
echo "⚙️  Warming up Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Run database migrations automatically
echo "🗄️  Executing database migrations..."
php artisan migrate --force

# 3. Create the storage symlink (required for public file access)
echo "🔗  Linking storage..."
php artisan storage:link --force 2>/dev/null || true

# 4. Launch Laravel Reverb WebSocket server in the background
echo "⚡  Starting Laravel Reverb WebSocket Server on port 8080..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# 5. Start background queue worker for async jobs (AI auto-reply, etc.)
echo "⚙️  Starting queue worker..."
php artisan queue:work --tries=3 --timeout=60 --sleep=3 &

# 6. Start Apache Web Server in the foreground
echo "📡  Starting Apache Web Server..."
exec apache2-foreground
