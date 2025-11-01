#!/usr/bin/env bash
set -euo pipefail

# -------------------------------------------------
#  Fresh-setup helper for ItemRAG (Laravel + Docker)
# -------------------------------------------------
#  1. Clean old containers and volumes
#  2. Fix host permissions
#  3. Setup Laravel directories
#  4. Build and start Docker Compose
#  5. Run Laravel setup commands
# -------------------------------------------------

echo "=========================================="
echo "  ItemRAG Fresh Setup Script"
echo "=========================================="
echo ""

# --- 1. Stop and clean old containers -----------------------------------------
echo "→ Stopping and removing old containers..."
docker compose down --remove-orphans || true

# Ask user if they want to remove volumes (fresh database)
read -p "Do you want to remove all volumes (fresh database)? (y/N): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "→ Removing all volumes..."
    docker compose down -v
    echo "✓ Volumes removed"
else
    echo "→ Keeping existing volumes"
fi

# --- 2. Clean up old files ---------------------------------------------------
echo ""
echo "→ Cleaning up old files..."

# Remove Orbit SQLite file if exists
if [[ -f database/orbit_meta.sqlite ]]; then
    echo "  • Removing old orbit_meta.sqlite..."
    sudo rm -f database/orbit_meta.sqlite 2>/dev/null || \
        echo "  ⚠ Warning: Could not remove orbit_meta.sqlite (may need manual cleanup)"
fi

# Remove vendor if exists (will be rebuilt in container)
if [[ -d vendor ]]; then
    read -p "  • Remove vendor directory? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "    Removing vendor directory (will be rebuilt)..."
        sudo rm -rf vendor 2>/dev/null || rm -rf vendor
    fi
fi

# Remove node_modules if needed
if [[ -d node_modules ]]; then
    read -p "  • Remove node_modules? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo rm -rf node_modules 2>/dev/null || rm -rf node_modules
        echo "  ✓ node_modules removed"
    fi
fi

# --- 3. Setup .env file ------------------------------------------------------
echo ""
echo "→ Setting up .env file..."
if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then
        echo "  • Copying .env.example to .env..."
        cp .env.example .env
        echo "  ✓ .env created"
    else
        echo "  ⚠ Warning: .env.example not found"
    fi
else
    echo "  • .env already exists"
fi

# --- 4. Create Laravel directories -------------------------------------------
echo ""
echo "→ Creating Laravel directories..."
mkdir -p \
    storage/app/public \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    bootstrap/cache \
    database \
    content/items

echo "  ✓ Directories created"

# --- 5. Fix ownership & permissions (www-data = 33:33) -----------------------
echo ""
echo "→ Setting ownership to www-data (33:33)..."

if command -v sudo &> /dev/null; then
    sudo chown -R 33:33 \
        storage \
        bootstrap/cache \
        database \
        content 2>/dev/null || \
        echo "  ⚠ Warning: Could not change ownership (permissions may cause issues)"

    echo "  • Setting directory permissions (755)..."
    sudo find storage bootstrap/cache database content \
        -type d -exec chmod 755 {} \; 2>/dev/null || true

    echo "  • Setting file permissions (644)..."
    sudo find storage bootstrap/cache database content \
        -type f -exec chmod 644 {} \; 2>/dev/null || true

    echo "  ✓ Permissions set"
else
    echo "  ⚠ Warning: sudo not available, skipping permission changes"
    echo "     You may need to manually fix permissions if issues occur"
fi

# --- 6. Build and start Docker Compose --------------------------------------
echo ""
read -p "Do you want to build with cache (faster)? (y/N): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "→ Building Docker images (with cache)..."
    docker compose build
else
    echo "→ Building Docker images (no cache - clean build)..."
    docker compose build --no-cache
fi

echo ""
echo "→ Starting services..."
docker compose up -d

# Wait for containers to be healthy
echo ""
echo "→ Waiting for containers to be ready..."
sleep 5

# --- 7. Fix vendor directory permissions AFTER containers start --------------
echo ""
echo "→ Fixing vendor directory permissions..."
# Create vendor directory on host with correct ownership
sudo mkdir -p vendor 2>/dev/null || mkdir -p vendor
sudo chown -R 33:33 vendor 2>/dev/null || true
sudo chmod -R 755 vendor 2>/dev/null || true

# --- 8. Run Laravel setup commands -------------------------------------------
echo ""
echo "→ Running Laravel setup commands..."

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "  • Generating application key..."
    docker compose exec -T app php artisan key:generate
else
    echo "  • Application key already set"
fi

# Install composer dependencies (in case volume mount didn't have them)
echo "  • Installing Composer dependencies..."
docker compose exec -T app composer install --no-dev --optimize-autoloader

# Run migrations
echo "  • Running database migrations..."
docker compose exec -T app php artisan migrate --force || \
    echo "  ⚠ Warning: Migrations failed (database may not be ready yet)"

# Clear and cache config
echo "  • Clearing and caching configuration..."
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear

# Create storage symlink
echo "  • Creating storage symlink..."
docker compose exec -T app php artisan storage:link || \
    echo "  ⚠ Storage link may already exist"

# --- 8. Final status ---------------------------------------------------------
echo ""
echo "=========================================="
echo "  ✓ Setup Complete!"
echo "=========================================="
echo ""
echo "Container Status:"
docker compose ps
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Next Steps:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "   Application:  http://localhost:8083"
echo "    MySQL:        localhost:3301"
echo "   Redis:         localhost:6379"
echo "   Qdrant:        http://localhost:6335"
echo ""
echo "Useful Commands:"
echo "  • Shell access:    docker compose exec app sh"
echo "  • View logs:       docker compose logs -f app"
echo "  • Run migrations:  docker compose exec app php artisan migrate"
echo "  • Run tests:       docker compose exec app php artisan test"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
