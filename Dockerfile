FROM php:8.3-apache

# Dépendances système
RUN apt-get update --fix-missing && apt-get install -y \
    libsqlite3-dev \
    libicu-dev \
    libseccomp-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN docker-php-ext-install pdo pdo_sqlite calendar

# Activer mod_rewrite + headers
RUN a2enmod rewrite headers

# Config Apache sécurisée
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
RUN echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/apache2.conf

# Fix NAS Synology — getrandom() non disponible sur noyaux anciens
# Technique : précharger une librairie qui intercepte getrandom()
# et le remplace par /dev/urandom via LD_PRELOAD
RUN cat > /usr/local/lib/getrandom_compat.c << 'CSRC'
#define _GNU_SOURCE
#include <stdio.h>
#include <string.h>
#include <sys/types.h>
#include <sys/syscall.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>

/* Intercepte getrandom() et le remplace par lecture de /dev/urandom */
ssize_t getrandom(void *buf, size_t buflen, unsigned int flags) {
    int fd = open("/dev/urandom", O_RDONLY);
    if (fd < 0) { errno = EIO; return -1; }
    ssize_t n = read(fd, buf, buflen);
    close(fd);
    return n;
}
CSRC
RUN apt-get update -qq && apt-get install -y gcc --no-install-recommends -qq \
    && gcc -shared -fPIC -o /usr/local/lib/getrandom_compat.so \
       /usr/local/lib/getrandom_compat.c \
    && rm /usr/local/lib/getrandom_compat.c \
    && apt-get remove -y gcc && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

# Injecter le LD_PRELOAD dans l'environnement Apache
RUN echo "export LD_PRELOAD=/usr/local/lib/getrandom_compat.so" \
    >> /etc/apache2/envvars

# Document root = public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-enabled/000-default.conf

# Copier l'application
COPY . /var/www/html/

# Entrypoint
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
