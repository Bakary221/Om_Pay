# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Installer les extensions nécessaires pour Composer
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-install gd

# Copier composer files
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts --ignore-platform-req=ext-gd

# Étape 2: Image finale pour l'application
FROM php:8.3-fpm-alpine

# Installer les extensions PHP nécessaires et bash pour Render
RUN apk add --no-cache postgresql-dev mysql-dev bash libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql gd

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Copier le reste du code de l'application
COPY . .

# Créer les répertoires nécessaires et définir les permissions - avant de changer d'utilisateur
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copier et rendre exécutable le script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Changer d'utilisateur après avoir créé les répertoires
USER laravel

# Exposer le port 9000 (port par défaut de Render)
EXPOSE 9000

# Commande par défaut pour Render
# 👇 Un seul CMD qui exécute le script de démarrage
CMD ["/usr/local/bin/start.sh"]


