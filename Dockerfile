FROM php:8.3-apache

# Dépendances système (retry en cas d'échec réseau ponctuel)
RUN apt-get update --fix-missing && apt-get install -y \
    libsqlite3-dev \
    libicu-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-install pdo pdo_sqlite calendar

# Activer mod_rewrite + headers
RUN a2enmod rewrite headers

# Config Apache sécurisée
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Désactiver la signature serveur
RUN echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/apache2.conf

# Document root = public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-enabled/000-default.conf

# Copier l'application
COPY . /var/www/html/

# Droits : app lisible par www-data, data en lecture/écriture, pas d'exec
RUN mkdir -p /var/www/html/data/archives \
    && chown -R root:www-data /var/www/html \
    && chmod -R 750 /var/www/html \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 700 /var/www/html/data \
    && chmod 750 /var/www/html/public

EXPOSE 80
