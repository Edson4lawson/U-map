# Dockerfile optimisé pour Render.com (free tier)
# Utilise PHP + Apache — serveur HTTP direct, pas besoin de nginx séparé

FROM php:8.4-apache

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    zip \
    unzip \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Configurer le DocumentRoot Apache vers /public de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configuration Apache pour Laravel (AllowOverride pour .htaccess)
RUN echo '<Directory "${APACHE_DOCUMENT_ROOT}">\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Copier les fichiers de l'application
WORKDIR /var/www/html

COPY . .

# Installer les dépendances PHP (production only)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist \
    --no-interaction \
    --ignore-platform-reqs

# Définir les permissions correctes
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copier et activer le script d'entrée
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render injecte le PORT via variable d'environnement
# Apache doit écouter sur ce port
ENV PORT=10000
EXPOSE 10000

# Exécuter l'entrypoint puis Apache
CMD ["/usr/local/bin/entrypoint.sh", "apache2-foreground"]
