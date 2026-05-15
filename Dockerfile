# =============================================================================
#  E-coomy — Root Dockerfile (for Render deployment)
#  The actual project lives in htdocs/extra-high-project/
# =============================================================================
FROM php:8.2-apache

# ── 1. System dependencies ────────────────────────────────────────────────────
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      default-mysql-client \
 && rm -rf /var/lib/apt/lists/*

# ── 2. PHP extensions ─────────────────────────────────────────────────────────
RUN docker-php-ext-install mysqli \
 && docker-php-ext-enable  mysqli

# ── 3. Apache modules ─────────────────────────────────────────────────────────
RUN a2enmod rewrite headers remoteip

# ── 4. PHP configuration (production baseline) ───────────────────────────────
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# ── 5. PHP overrides ──────────────────────────────────────────────────────────
RUN { \
      echo "session.save_path = /var/lib/php/sessions"; \
      echo "upload_max_filesize = 10M"; \
      echo "post_max_size = 12M"; \
      echo "display_errors = Off"; \
      echo "log_errors = On"; \
    } >> "$PHP_INI_DIR/php.ini"

# ── 6. Apache virtual-host config ────────────────────────────────────────────
COPY htdocs/extra-high-project/docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ── 7. Copy project files into DocumentRoot ───────────────────────────────────
COPY htdocs/extra-high-project/ /var/www/html/

# ── 8. Remove files that must not be served publicly ─────────────────────────
RUN rm -f /var/www/html/.env \
          /var/www/html/.env.example \
          /var/www/html/docker-compose.yml \
          /var/www/html/Dockerfile \
          /var/www/html/README.txt \
          /var/www/html/CHECKLIST.txt

# ── 9. Session directory + permissions ───────────────────────────────────────
RUN mkdir -p /var/lib/php/sessions \
 && chown -R www-data:www-data /var/www/html /var/lib/php/sessions \
 && chmod -R 755 /var/www/html \
 && chmod -R 700 /var/lib/php/sessions

EXPOSE 80
