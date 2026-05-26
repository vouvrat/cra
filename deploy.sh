#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Déploiement complet de l'application CRA
# Usage : ./deploy.sh [--port 8080] [--upgrade]
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*"; exit 1; }

# ── Arguments ────────────────────────────────────────────────────────────────
PORT=8080
UPGRADE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --port)    PORT="$2"; shift 2 ;;
        --upgrade) UPGRADE=true; shift ;;
        --help)
            echo "Usage: ./deploy.sh [--port PORT] [--upgrade]"
            echo "  --port PORT   Port HTTP exposé (défaut: 8080)"
            echo "  --upgrade     Mise à jour sans perte de données"
            exit 0 ;;
        *) error "Argument inconnu: $1" ;;
    esac
done

# ── Vérifications préalables ─────────────────────────────────────────────────
info "Vérification de l'environnement..."

[[ -f "docker-compose.yml" ]] || error "Ce script doit être lancé depuis le répertoire racine du projet CRA."

command -v docker >/dev/null 2>&1 || error "Docker n'est pas installé. Installez-le avec : curl -fsSL https://get.docker.com | sh"

# ── Résolution des droits Docker ─────────────────────────────────────────────
# Tester si Docker est accessible sans sudo
DOCKER_OK=false
if docker info >/dev/null 2>&1; then
    DOCKER_OK=true
fi

if [[ "$DOCKER_OK" == "false" ]]; then
    warn "L'utilisateur $(whoami) n'a pas accès au socket Docker."

    # Vérifier si sudo est disponible
    if ! command -v sudo >/dev/null 2>&1; then
        error "sudo n'est pas disponible. Lancez ce script en root ou ajoutez l'utilisateur au groupe docker."
    fi

    # Vérifier si sudo docker fonctionne
    if ! sudo docker info >/dev/null 2>&1; then
        error "Impossible d'accéder à Docker même avec sudo. Vérifiez que Docker est démarré : sudo systemctl start docker"
    fi

    # Proposer d'ajouter l'utilisateur au groupe docker (persistant)
    echo ""
    echo -e "${YELLOW}Options :${NC}"
    echo -e "  ${GREEN}1)${NC} Ajouter $(whoami) au groupe docker (recommandé — nécessite reconnexion)"
    echo -e "  ${GREEN}2)${NC} Continuer avec sudo pour cette session uniquement"
    echo ""
    read -rp "Choix [1/2] : " CHOICE

    case "$CHOICE" in
        1)
            info "Ajout de $(whoami) au groupe docker..."
            sudo usermod -aG docker "$(whoami)"
            success "Utilisateur ajouté au groupe docker."
            echo ""
            echo -e "${YELLOW}⚠ Vous devez vous reconnecter pour que le changement prenne effet.${NC}"
            echo -e "  Après reconnexion, relancez : ${GREEN}./deploy.sh${NC}"
            echo ""
            echo -e "  Ou pour continuer maintenant sans reconnexion :"
            echo -e "  ${GREEN}newgrp docker${NC} puis ${GREEN}./deploy.sh${NC}"
            echo ""
            # Proposer de continuer via newgrp dans la même session
            read -rp "Continuer avec 'newgrp docker' maintenant ? [o/N] : " NEWGRP
            if [[ "${NEWGRP,,}" == "o" ]]; then
                info "Relancement du script avec newgrp docker..."
                exec newgrp docker <<NGEOF
cd "$(pwd)" && bash deploy.sh $([ "$UPGRADE" == "true" ] && echo "--upgrade") --port "$PORT"
NGEOF
            else
                exit 0
            fi
            ;;
        2)
            info "Utilisation de sudo pour cette session..."
            SUDO="sudo"
            ;;
        *)
            error "Choix invalide."
            ;;
    esac
else
    SUDO=""
fi

# Construire la commande docker compose avec ou sans sudo
if $SUDO docker compose version >/dev/null 2>&1; then
    DC="$SUDO docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    DC="$SUDO docker-compose"
else
    error "docker compose introuvable. Installez le plugin : sudo apt-get install docker-compose-plugin"
fi

success "Environnement OK — commande: $DC"

# ── Mise à jour du port ───────────────────────────────────────────────────────
if grep -q "8080:80" docker-compose.yml && [[ "$PORT" != "8080" ]]; then
    info "Mise à jour du port vers $PORT..."
    sed -i "s/8080:80/$PORT:80/" docker-compose.yml
fi

# ── Arrêt / sauvegarde ────────────────────────────────────────────────────────
if [[ "$UPGRADE" == "true" ]]; then
    warn "Mode UPGRADE — les données existantes seront conservées."
    $DC down || true

    BACKUP_FILE="cra_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    info "Sauvegarde des données vers $BACKUP_FILE..."
    $SUDO docker run --rm \
        -v cra_cra_data:/data \
        -v "$(pwd)":/backup \
        alpine tar czf /backup/"$BACKUP_FILE" -C /data . 2>/dev/null \
        && success "Backup créé : $BACKUP_FILE" \
        || warn "Impossible de sauvegarder le volume (n'existe peut-être pas encore)."
else
    info "Arrêt des containers existants..."
    $DC down 2>/dev/null || true
fi

# ── Build ─────────────────────────────────────────────────────────────────────
info "Construction de l'image Docker (peut prendre quelques minutes)..."
$DC build --no-cache

# ── Démarrage ─────────────────────────────────────────────────────────────────
info "Démarrage du container..."
$DC up -d

# ── Healthcheck ───────────────────────────────────────────────────────────────
info "Attente du démarrage..."
sleep 8

MAX_RETRIES=6; RETRY=0
while [[ $RETRY -lt $MAX_RETRIES ]]; do
    if curl -sf "http://localhost:$PORT/" >/dev/null 2>&1; then
        break
    fi
    RETRY=$((RETRY+1))
    [[ $RETRY -lt $MAX_RETRIES ]] && { warn "Tentative $RETRY/$MAX_RETRIES..."; sleep 5; }
done

if [[ $RETRY -eq $MAX_RETRIES ]]; then
    echo ""
    warn "L'application ne répond pas encore sur le port $PORT."
    warn "Vérifiez les logs avec : $DC logs"
    warn "Il est possible que le démarrage soit simplement lent — réessayez dans quelques secondes."
    exit 1
fi

# ── Droits volume ─────────────────────────────────────────────────────────────
$SUDO docker exec cra-app chown -R www-data:www-data /var/www/html/data 2>/dev/null || true
$SUDO docker exec cra-app chmod -R 700 /var/www/html/data 2>/dev/null || true

# ── Résumé ────────────────────────────────────────────────────────────────────
IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓  Application CRA déployée avec succès !${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  URL         : ${BLUE}http://${IP}:${PORT}${NC}"
echo -e "  Login       : ${YELLOW}admin${NC}  /  ${YELLOW}admin123${NC}  ${RED}← CHANGER IMMÉDIATEMENT${NC}"
echo ""
echo -e "  Commandes utiles :"
echo -e "    Logs      : ${BLUE}$DC logs -f${NC}"
echo -e "    Statut    : ${BLUE}$DC ps${NC}"
echo -e "    Arrêt     : ${BLUE}$DC down${NC}"
echo -e "    Mise à jour: ${BLUE}./deploy.sh --upgrade${NC}"
echo -e "    Purge tot. : ${BLUE}./purge.sh${NC}"
echo ""

# Rappel groupe docker si sudo a été utilisé
if [[ "${SUDO:-}" == "sudo" ]]; then
    echo -e "${YELLOW}[RAPPEL]${NC} Pour ne plus avoir besoin de sudo :"
    echo -e "  ${GREEN}sudo usermod -aG docker $(whoami) && newgrp docker${NC}"
    echo ""
fi
