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

# 3. .env: use a bind-mounted file if present, otherwise generate one from the container
#    environment (EasyPanel "Environment" tab). Container variables always take precedence.
if [ ! -f .env ]; then
  echo "No .env mounted; generating /var/www/html/.env from container environment variables"
  : > .env
  for k in APP_ENV APP_NAME APP_KEY APP_DEBUG APP_URL APP_MODE APP_TIMEZONE PRODUCT_KEY AD_PRODUCT_ID \
           LOG_CHANNEL LOG_DEPRECATIONS_CHANNEL LOG_LEVEL \
           DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD \
           QUEUE_CONNECTION CACHE_DRIVER SESSION_DRIVER SESSION_LIFETIME SESSION_SECURE_COOKIE FILESYSTEM_DISK BROADCAST_DRIVER \
           MAIL_MAILER MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_ENCRYPTION MAIL_FROM_ADDRESS MAIL_FROM_NAME \
           PUSHER_APP_ID PUSHER_APP_KEY PUSHER_APP_SECRET PUSHER_HOST PUSHER_PORT PUSHER_SCHEME PUSHER_APP_CLUSTER; do
    if v=$(printenv "$k" 2>/dev/null); then
      printf '%s="%s"\n' "$k" "$v" >> .env
    fi
  done
fi
if [ -z "$(printenv APP_KEY 2>/dev/null)" ] && ! grep -q '^APP_KEY=.\{10,\}' .env; then
  echo "FATAL: APP_KEY is not set (set it in the EasyPanel Environment tab)" >&2
  exit 1
fi

chown -R www-data:www-data storage bootstrap/cache public/backend/images public/backend/files \
      public/frontend/images public/frontend/user public/fileholder lang
chown www-data:www-data .env && chmod 664 .env

# 4. Rebuild bootstrap/cache/packages.php (composer ran with --no-scripts). Harmless if it fails before the DB exists.
gosu www-data php artisan package:discover --ansi >/dev/null 2>&1 || true

# 5. Staging convenience: run the queue worker and scheduler inside the web container when
#    RUN_WORKERS=true. Each runs in a restart loop so a crash does not stop background processing.
#    Leave RUN_WORKERS unset if you run dedicated scheduler/worker services instead.
if [ "${RUN_WORKERS:-false}" = "true" ] && [ "$1" != "php" ]; then
  echo "RUN_WORKERS=true: starting queue worker and scheduler in the background"
  gosu www-data sh -c 'while true; do php artisan queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600 --timeout=120; sleep 5; done' &
  gosu www-data sh -c 'while true; do php artisan schedule:work; sleep 5; done' &
fi

# 6. Artisan commands (dedicated scheduler / worker services) run as www-data; Apache drops privileges itself
if [ "$1" = "php" ]; then
  exec gosu www-data "$@"
fi
exec "$@"
