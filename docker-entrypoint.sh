#!/bin/bash
set -e

# Fix NAS Synology — getrandom() syscall absent sur noyaux < 3.17
# LD_PRELOAD intercepte getrandom() et le remplace par /dev/urandom
if [ -f /usr/local/lib/getrandom_compat.so ]; then
    export LD_PRELOAD=/usr/local/lib/getrandom_compat.so
fi

# S'assurer que /dev/urandom est accessible
if [ ! -r /dev/urandom ]; then
    mknod -m 444 /dev/urandom c 1 9 2>/dev/null || true
fi

exec apache2-foreground "$@"
