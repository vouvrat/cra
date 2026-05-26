#!/usr/bin/env bash
# =============================================================================
# update.sh — Mise à jour de l'application CRA depuis GitHub
# Usage : ./update.sh [--no-rebuild]
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

REBUILD=true
while [[ $# -gt 0 ]]; do
    case $1 in
        --no-rebuild) REBUILD=false; shift ;;
        --help)
            echo "Usage: ./update.sh [--no-rebuild]"
            echo "  --no-rebuild  Git pull uniquement, sans rebuild Docker"
            exit 0 ;;
        *) error "Argument inconnu: $1" ;;
    esac
done

[[ -f "docker-compose.yml" ]] || error "Lancez ce script depuis le répertoire racine du projet CRA."
[[ -d ".git" ]] || error "Ce répertoire n'est pas un dépôt git. Clonez d'abord le projet."

# ── Résolution sudo Docker ───────────────────────────────────────────────────
if docker info >/dev/null 2>&1; then
    SUDO=""
elif sudo docker info >/dev/null 2>&1; then
    SUDO="sudo"
else
    error "Docker inaccessible."
fi
DC=$($SUDO docker compose version >/dev/null 2>&1 && echo "$SUDO docker compose" || echo "$SUDO docker-compose")

# ── Vérifier les modifications locales ──────────────────────────────────────
if ! git diff --quiet || ! git diff --cached --quiet; then
    warn "Des modifications locales existent :"
    git status --short
    echo ""
    read -rp "Les écraser avec la version GitHub ? (oui/non) : " CHOICE
    [[ "$CHOICE" == "oui" ]] || { echo "Annulé."; exit 0; }
    git checkout -- .
fi

# ── Git pull ──────────────────────────────────────────────────────────────────
info "Récupération des dernières modifications depuis GitHub..."
BEFORE=$(git rev-parse HEAD)
git pull origin main 2>/dev/null || git pull origin master 2>/dev/null || error "git pull échoué. Vérifiez votre connexion et vos droits."
AFTER=$(git rev-parse HEAD)

if [[ "$BEFORE" == "$AFTER" ]]; then
    success "Déjà à jour — aucune modification."
    exit 0
fi

# Afficher le changelog
echo ""
echo -e "${BLUE}Modifications appliquées :${NC}"
git log --oneline "$BEFORE".."$AFTER"
echo ""

# ── Rebuild Docker si nécessaire ─────────────────────────────────────────────
if [[ "$REBUILD" == "true" ]]; then
    # Sauvegarder les données avant le rebuild
    BACKUP_FILE="cra_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    info "Sauvegarde des données vers $BACKUP_FILE..."
    $SUDO docker run --rm \
        -v cra_cra_data:/data \
        -v "$(pwd)":/backup \
        alpine tar czf /backup/"$BACKUP_FILE" -C /data . 2>/dev/null \
        && success "Backup : $BACKUP_FILE" \
        || warn "Impossible de sauvegarder (volume inexistant ?)."

    info "Rebuild de l'image Docker..."
    $DC build --no-cache

    info "Redémarrage du container..."
    $DC up -d

    # Attendre que le container soit prêt
    sleep 6
    PORT=$(grep -oP '"\K[0-9]+(?=:80")' docker-compose.yml 2>/dev/null || echo "8080")
    RETRY=0
    while [[ $RETRY -lt 5 ]]; do
        curl -sf "http://localhost:$PORT/" >/dev/null 2>&1 && break
        RETRY=$((RETRY+1)); sleep 3
    done
    [[ $RETRY -lt 5 ]] || warn "Le container met du temps à démarrer. Vérifiez avec : $DC logs"

    # Droits volume
    $SUDO docker exec cra-app chown -R www-data:www-data /var/www/html/data 2>/dev/null || true

else
    # Sans rebuild : copier uniquement les fichiers PHP/assets dans le container
    info "Mise à jour des fichiers sans rebuild (--no-rebuild)..."
    $SUDO docker cp app/   cra-app:/var/www/html/app/
    $SUDO docker cp public/index.php cra-app:/var/www/html/public/index.php
    $SUDO docker cp public/.htaccess cra-app:/var/www/html/public/.htaccess
    success "Fichiers mis à jour dans le container."
fi

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓  Mise à jour appliquée avec succès !${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  Version : ${BLUE}$(git rev-parse --short HEAD)${NC} — $(git log -1 --format='%s')"
echo -e "  Logs    : ${BLUE}$DC logs -f${NC}"
echo ""
