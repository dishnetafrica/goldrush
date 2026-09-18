# GoldInvest staging image (EasyPanel). Build context = repository root, Dockerfile = deploy/staging/Dockerfile.
# The application under goldinvest-web/goldinvest-web-new-v1.0.0/ is copied unmodified.
FROM php:8.2-apache

# System packages: build deps for gd/zip, mysql client for backups, gosu to drop privileges, curl for the health check
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl gosu default-mysql-client \
        libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd zip pdo_mysql exif opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Extensions already built into php:8.2: ctype curl dom fileinfo filter hash iconv json libxml mbstring openssl
# pcre pdo session simplexml sodium tokenizer xml xmlreader xmlwriter zlib

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY deploy/staging/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/staging/php.ini            /usr/local/etc/php/conf.d/zz-goldinvest.ini
COPY deploy/staging/entrypoint.sh      /usr/local/bin/goldinvest-entrypoint

WORKDIR /var/www/html
COPY goldinvest-web/goldinvest-web-new-v1.0.0/ /var/www/html/

# --no-scripts: package:discover needs a bootable app; the entrypoint runs it at container start instead.
# /opt/goldinvest-defaults keeps a pristine copy of directories that are bind-mounted at runtime.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts \
    && mkdir -p /opt/goldinvest-defaults \
    && cp -a --parents public/backend/images public/backend/files public/frontend/images \
                      public/frontend/user public/fileholder lang /opt/goldinvest-defaults/ \
    && chmod +x /usr/local/bin/goldinvest-entrypoint \
    && chown -R www-data:www-data /var/www/html

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
  CMD test "$(curl -s -o /dev/null -w '%{http_code}' http://localhost/login)" = "200" || exit 1

ENTRYPOINT ["goldinvest-entrypoint"]
CMD ["apache2-foreground"]
