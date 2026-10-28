FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html/

# The source archive is flattened; recreate the paths used by the PHP pages.
RUN mkdir -p config includes admin teacher student assets/css assets/js database uploads \
    && cp db.php config/db.php \
    && cp db.php includes/db.php \
    && cp auth.php functions.php header.php footer.php navbar.php sidebar.php includes/ \
    && cp style.css assets/css/style.css \
    && cp main.js assets/js/main.js \
    && cp schema.sql database/schema.sql \
    && cp 'index (8).php' index.php \
    && for f in *.php; do \
         case "$f" in \
           'index (8).php'|'index (2).php'|'index (7).php'|'index.php'|db.php|auth.php|functions.php|header.php|footer.php|navbar.php|sidebar.php) continue ;; \
         esac; \
         clean=$(printf '%s' "$f" | sed -E 's/ *\([0-9]+\)\.php$/.php/'); \
         cp "$f" "$clean"; \
         cp "$f" "admin/$clean"; \
         cp "$f" "teacher/$clean"; \
         cp "$f" "student/$clean"; \
       done \
    && cp 'index (2).php' student/index.php \
    && cp 'index (7).php' teacher/index.php \
    && for f in /var/www/html/admin/*.php /var/www/html/teacher/*.php /var/www/html/student/*.php; do \
         [ -f "$f" ] || continue; \
         case "$f" in \
           */admin/*) role=admin ;; \
           */teacher/*) role=teacher ;; \
           */student/*) role=student ;; \
         esac; \
         case "$f" in \
           */index.php) continue ;; \
         esac; \
         src="/var/www/html/$(basename "$f")"; \
         [ -f "$src" ] || continue; \
         if grep -q "require_role('$role')\|require_role(\"$role\")" "$src"; then cp "$src" "$f"; fi; \
       done \
    && chown -R www-data:www-data /var/www/html \
    && sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's#<VirtualHost .*:80>#<VirtualHost *:10000>#' /etc/apache2/sites-available/000-default.conf

ENV PORT=10000
EXPOSE 10000
CMD ["apache2-foreground"]
