#!/usr/bin/env bash
# synology-update.sh — Mise à jour de l'image CRA sur Synology
# Placer ce script sur le NAS et l'exécuter manuellement ou via planificateur
set -euo pipefail

IMAGE="vouvrat/cra:latest"
CONTAINER="cra-app"
DATA_DIR="/volume1/docker/cra/data"

echo "[CRA] Vérification des mises à jour..."

# Sauvegarder les données
BACKUP="$DATA_DIR/../cra_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
tar czf "$BACKUP" -C "$DATA_DIR" . 2>/dev/null && echo "[CRA] Backup : $BACKUP"

# Puller la nouvelle image
docker pull "$IMAGE"

# Redémarrer si une nouvelle version est disponible
docker stop "$CONTAINER"  2>/dev/null || true
docker rm   "$CONTAINER"  2>/dev/null || true

docker run -d \
  --name "$CONTAINER" \
  --restart unless-stopped \
  -p 8080:80 \
  -v "$DATA_DIR:/var/www/html/data" \
  "$IMAGE"

echo "[CRA] Mise à jour terminée — $(docker inspect --format='{{.Image}}' $CONTAINER | cut -c1-12)"
