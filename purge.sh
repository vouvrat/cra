#!/usr/bin/env bash
# purge.sh — Supprime tous les containers, images et volumes du projet CRA
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# Résolution sudo
if docker info >/dev/null 2>&1; then
    SUDO=""
elif sudo docker info >/dev/null 2>&1; then
    SUDO="sudo"
    warn "Utilisation de sudo pour accéder à Docker."
else
    error "Docker inaccessible. Vérifiez que Docker est démarré."
fi

DC=$($SUDO docker compose version >/dev/null 2>&1 && echo "$SUDO docker compose" || echo "$SUDO docker-compose")

echo -e "${YELLOW}⚠  Cette opération va supprimer :${NC}"
echo "   - Le container  : cra-app"
echo "   - L'image Docker: cra-cra (ou cra_cra)"
echo "   - Le volume data: cra_cra_data (BDD + archives)"
echo ""
read -rp "Confirmer ? (oui/non) : " CONFIRM
[[ "$CONFIRM" != "oui" ]] && { echo "Annulé."; exit 0; }

warn "Arrêt et suppression des containers + volumes..."
$DC down -v 2>/dev/null || true

warn "Suppression de l'image..."
$SUDO docker rmi cra-cra  2>/dev/null || true
$SUDO docker rmi cra_cra  2>/dev/null || true

warn "Nettoyage des images orphelines..."
$SUDO docker image prune -f 2>/dev/null || true

echo ""
success "Environnement purgé."
echo -e "Pour redéployer : ${GREEN}./deploy.sh${NC}"
