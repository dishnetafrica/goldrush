#!/bin/sh
# GoldInvest staging entrypoint: seeds bind mounts, fixes permissions, then runs the service command.
set -e
cd /var/www/html

# 1. Seed bind-mounted upload/lang directories with the files shipped in the image (never overwrite existing files)
for d in public/backend/images public/backend/files public/frontend/images public/frontend/user public/fileholder lang; do
  mkdir -p "$d"
  cp -an "/opt/goldinvest-defaults/$d/." "$d/" 2>/dev/null || true
done

# 2. Laravel writable tree on the storage volume
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/framework/testing storage/logs bootstrap/cache

# 3. .env must be bind-mounted; refuse to start without it
if [ ! -f .env ]; then
  echo "FATAL: /var/www/html/.env is not mounted (see runbook section 7 and 8)" >&2
  exit 1
fi

chown -R www-data:www-data storage bootstrap/cache public/backend/images public/backend/files \
      public/frontend/images public/frontend/user public/fileholder lang
chown www-data:www-data .env && chmod 664 .env

# 4. Rebuild bootstrap/cache/packages.php (composer ran with --no-scripts). Harmless if it fails before the DB exists.
gosu www-data php artisan package:discover --ansi >/dev/null 2>&1 || true

# 5. Artisan commands (scheduler / worker services) run as www-data; Apache drops privileges by itself
if [ "$1" = "php" ]; then
  exec gosu www-data "$@"
fi
exec "$@"
