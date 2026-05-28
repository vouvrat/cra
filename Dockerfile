FROM php:8.3-apache

# Dépendances système
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
RUN echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/apache2.conf

# Fix compatibilité NAS Synology / noyaux < 3.17 / ARM 32-bit
# AH00141: Could not initialize random number generator
# Apache tente d'utiliser le syscall getrandom() absent sur ces noyaux.
# On force l'utilisation de /dev/urandom à la place.
RUN echo "export APACHE_RNG_DEFAULT=file:/dev/urandom" >> /etc/apache2/envvars

# Document root = public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-enabled/000-default.conf

# Copier l'application
COPY . /var/www/html/

# Copier l'entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Droits
RUN mkdir -p /var/www/html/data/archives \
    && chown -R root:www-data /var/www/html \
    && chmod -R 750 /var/www/html \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 700 /var/www/html/data \
    && chmod 750 /var/www/html/public

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
