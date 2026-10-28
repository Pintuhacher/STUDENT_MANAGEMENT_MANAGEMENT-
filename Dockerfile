FROM php:8.2-apache

# Install MySQL PDO extension and local MariaDB server (fallback if remote DB blocks Render)
RUN apt-get update && apt-get install -y mariadb-server \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

# Reconstruct directory structure (config/, includes/, admin/, teacher/, student/, assets/, database/) from flattened files
RUN cd /var/www/html \
    && mkdir -p config includes admin teacher student assets/css assets/js database uploads \
    && for f in db.php auth.php functions.php header.php footer.php navbar.php sidebar.php; do \
         if [ -f "$f" ]; then \
           cp "$f" "includes/$f"; \
           cp "$f" "config/$f"; \
         fi; \
       done \
    && [ -f style.css ] && cp style.css assets/style.css && cp style.css assets/css/style.css || true \
    && [ -f main.js ] && cp main.js assets/main.js && cp main.js assets/js/main.js || true \
    && [ -f schema.sql ] && cp schema.sql database/schema.sql || true \
    && mkdir -p /tmp/orig && cp *.php /tmp/orig/ \
    && for f in /tmp/orig/*.php; do \
         base=$(basename "$f"); \
         clean=$(echo "$base" | sed -E 's/ *\([0-9]+\)\.php$/.php/'); \
         case "$clean" in \
           db.php|auth.php|functions.php|header.php|footer.php|navbar.php|sidebar.php|index.php) continue ;; \
         esac; \
         cp "$f" "$clean"; \
         cp "$f" "admin/$clean"; \
         cp "$f" "teacher/$clean"; \
         cp "$f" "student/$clean"; \
       done \
    && for f in /tmp/orig/*.php; do \
         base=$(basename "$f"); \
         clean=$(echo "$base" | sed -E 's/ *\([0-9]+\)\.php$/.php/'); \
         case "$clean" in \
           db.php|auth.php|functions.php|header.php|footer.php|navbar.php|sidebar.php) continue ;; \
         esac; \
         if grep -qE "require_role\s*\(\s*['\"]admin['\"]\s*\)" "$f"; then \
           cp "$f" "admin/$clean"; \
         elif grep -qE "require_role\s*\(\s*['\"]teacher['\"]\s*\)" "$f"; then \
           cp "$f" "teacher/$clean"; \
         elif grep -qE "require_role\s*\(\s*['\"]student['\"]\s*\)" "$f"; then \
           cp "$f" "student/$clean"; \
         elif [ "$clean" = "index.php" ]; then \
           cp "$f" "index.php"; \
         fi; \
       done \
    && rm -rf /tmp/orig \
    && ln -sf /var/www/html/config /var/www/config \
    && ln -sf /var/www/html/includes /var/www/includes \
    && ln -sf /var/www/html/assets /var/www/assets

# Add automatic local MariaDB fallback to db.php in root, config/, and includes/
RUN cat << 'EOF' > /var/www/html/db.php
<?php
$host = getenv('DB_HOST') ?: 'sql108.freehosting.digitalplat.org';
$db   = getenv('DB_NAME') ?: 'alalr_43295771_surajitdb';
$user = getenv('DB_USER') ?: 'alalr_43295771';
$pass = getenv('DB_PASS') ?: 'INU573Wl54n93nVW5NU03wValzoM';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 3,
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $dsn_local = "mysql:host=127.0.0.1;port=3306;dbname=edudash;charset=$charset";
    $pdo = new PDO($dsn_local, 'edudash', 'edudash123', $options);
}
?>
EOF
RUN cp /var/www/html/db.php /var/www/html/config/db.php \
    && cp /var/www/html/db.php /var/www/html/includes/db.php

# Initialize local MariaDB with schema.sql and configure Apache port
ENV PORT=10000
RUN service mariadb start \
    && mysql -e "CREATE DATABASE IF NOT EXISTS edudash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    && mysql -e "CREATE USER IF NOT EXISTS 'edudash'@'127.0.0.1' IDENTIFIED BY 'edudash123'; GRANT ALL PRIVILEGES ON edudash.* TO 'edudash'@'127.0.0.1'; FLUSH PRIVILEGES;" \
    && mysql -e "CREATE USER IF NOT EXISTS 'edudash'@'localhost' IDENTIFIED BY 'edudash123'; GRANT ALL PRIVILEGES ON edudash.* TO 'edudash'@'localhost'; FLUSH PRIVILEGES;" \
    && (grep -viE '^\s*(CREATE\s+DATABASE|USE)\s+' /var/www/html/schema.sql | mysql edudash || true) \
    && chown -R www-data:www-data /var/www/html \
    && sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 10000
CMD service mariadb start && apache2-foreground
