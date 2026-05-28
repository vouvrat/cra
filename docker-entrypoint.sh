#!/bin/bash
# Entrypoint custom — compatibilité NAS Synology et noyaux anciens

# Forcer /dev/urandom pour Apache (évite "Function not implemented: getrandom")
# Nécessaire sur les NAS Synology avec noyaux Linux < 3.17 ou ARM 32-bit
export APACHE_RNG_DEFAULT=file:/dev/urandom

# Appliquer la configuration si le fichier existe
if [ -f /etc/apache2/conf-available/security.conf ]; then
    # Désactiver les tentatives d'utilisation de getrandom
    sed -i 's/^#\?RNG.*//' /etc/apache2/conf-available/security.conf 2>/dev/null || true
fi

# Patcher le module de génération aléatoire d'Apache
if [ -f /usr/lib/apache2/modules/mod_ssl.so ] || [ -f /etc/apache2/mods-enabled/ssl.load ]; then
    # SSL présent — s'assurer que /dev/urandom est lisible
    chmod 444 /dev/urandom 2>/dev/null || true
fi

# Vérifier que /dev/urandom est bien disponible
if [ ! -r /dev/urandom ]; then
    mknod -m 444 /dev/urandom c 1 9 2>/dev/null || true
fi

# Lancer Apache normalement
exec apache2-foreground "$@"
