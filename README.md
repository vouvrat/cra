# CRA — Compte Rendu d'Activité

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker)](https://docker.com)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite)](https://sqlite.org)
[![License](https://img.shields.io/badge/Licence-MIT-green)](LICENSE)

Application web de suivi d'activité professionnelle (CRA) auto-hébergée.  
Multi-utilisateurs, gestion d'équipes, délégations, thème clair/sombre, support mobile (PWA).

---

## Fonctionnalités

- **Saisie journalière** — Présentiel, Télétravail, RTT, Congé payé, Sans solde, Férié
- **Demi-journées** — double-clic pour diviser une journée en matin/après-midi avec types différents
- **Vue mensuelle** — calendrier interactif (clic = journée, double-clic = demi-journées, note)
- **Vue annuelle** — récapitulatif avec calcul automatique trajets/indemnités
- **Config trajets par périodes** — historique des distances/durées (déménagement, changement de poste)
- **Multi-utilisateurs** — rôles `admin` / `user`
- **Équipes** — responsable gère les CRA de ses membres, y compris **comptes virtuels** (sans accès)
- **Délégations** — consultation du CRA d'un autre utilisateur (lecture seule)
- **Archives** — snapshots JSON par utilisateur ou équipe, téléchargeables
- **Export CSV** — par utilisateur ou par équipe, avec km/durée/indemnités par ligne
- **Thème clair / sombre** — bascule par utilisateur, persisté localement
- **PWA mobile** — icône raccourci écran d'accueil, mode standalone
- **Fériés français** calculés dynamiquement pour chaque année
- **Sécurité** — CSRF, sessions sécurisées, bcrypt, requêtes préparées PDO

---

## Déploiement rapide (serveur Linux)

### Prérequis
- Docker Engine ≥ 24
- Docker Compose plugin ≥ 2

### Installation

```bash
git clone https://github.com/vouvrat/cra.git
cd cra
chmod +x deploy.sh update.sh purge.sh
./deploy.sh
```

Accès : `http://IP_SERVEUR:8080`

> **Compte par défaut :** `admin` / `admin123` — **à changer immédiatement** via Admin → Utilisateurs.

### Options

```bash
./deploy.sh --port 9090    # port personnalisé (défaut: 8080)
./deploy.sh --upgrade      # mise à jour avec backup automatique
./update.sh                # git pull + rebuild + redémarrage
./update.sh --no-rebuild   # git pull + copie des fichiers uniquement
./purge.sh                 # suppression complète containers + volumes + image
```

---

## Déploiement sur NAS Synology

### Prérequis

- DSM 7.2 ou supérieur
- **Container Manager** installé depuis le Centre de paquets

### Méthode 1 — Docker Hub (recommandée)

#### Étape 1 — Publier l'image (depuis ton serveur Linux)

```bash
# Se connecter à Docker Hub
docker login
# Entrer ton identifiant et mot de passe Docker Hub

# Builder l'image
cd cra
docker build -t TON_USER_DOCKERHUB/cra:latest .

# Publier
docker push TON_USER_DOCKERHUB/cra:latest
```

#### Étape 2 — Préparer le dossier de données sur le NAS

Connecte-toi au NAS via SSH (activer dans DSM → Terminal et SNMP) :

```bash
ssh admin@IP_DU_NAS

# Créer la structure de dossiers
mkdir -p /volume1/docker/cra/data/archives
chmod -R 755 /volume1/docker/cra/data
```

#### Étape 3 — Déployer via Container Manager

**Option A — Interface graphique :**

1. Container Manager → **Registre** → Rechercher `TON_USER_DOCKERHUB/cra`
2. Télécharger l'image `latest`
3. Container Manager → **Conteneur** → **Créer**
4. Configurer :

| Paramètre | Valeur |
|-----------|--------|
| Image | `TON_USER_DOCKERHUB/cra:latest` |
| Nom | `cra-app` |
| Port hôte | `8080` |
| Port container | `80` |
| Volume hôte | `/volume1/docker/cra/data` |
| Volume container | `/var/www/html/data` |
| Redémarrage | Toujours |

**Option B — docker-compose (via Container Manager → Projet) :**

Créer un fichier `/volume1/docker/cra/docker-compose.yml` :

```yaml
services:
  cra:
    image: TON_USER_DOCKERHUB/cra:latest
    container_name: cra-app
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - /volume1/docker/cra/data:/var/www/html/data
    dns:
      - 8.8.8.8
      - 1.1.1.1
    environment:
      - APACHE_RUN_USER=www-data
      - APACHE_RUN_GROUP=www-data
```

Dans Container Manager → **Projet** → **Créer** → pointer vers ce fichier.

#### Étape 4 — Corriger les permissions (une seule fois)

```bash
docker exec cra-app chown -R www-data:www-data /var/www/html/data
docker exec cra-app chmod -R 700 /var/www/html/data
```

### Méthode 2 — Copie directe de l'image

Si tu ne veux pas utiliser Docker Hub :

```bash
# Sur le serveur Linux — exporter l'image
docker save vouvrat/cra:latest | gzip > cra-image.tar.gz

# Copier sur le NAS
scp cra-image.tar.gz admin@IP_NAS:/volume1/docker/cra/

# Sur le NAS — importer
docker load < /volume1/docker/cra/cra-image.tar.gz
```

Puis déployer avec le docker-compose ci-dessus en remplaçant l'image par `vouvrat/cra:latest`.

---

## Rendre accessible depuis l'extérieur

### Option A — Accès local uniquement

Accessible sur le réseau local : `http://IP_NAS:8080`

### Option B — Tailscale (recommandé, zéro configuration)

VPN mesh gratuit — accès sécurisé depuis n'importe où sans ouvrir de port.

```bash
# Sur le NAS — installer le paquet Tailscale depuis Centre de paquets
# Sur chaque appareil — installer l'app Tailscale (iOS, Android, PC)
# Accès : http://NOM_NAS:8080
```

### Option C — HTTPS avec nom de domaine (accès public)

1. **Nom de domaine** — OVH, Cloudflare (~10€/an) ou DynDNS gratuit (ex: `cra.monnom.synology.me`)

2. **Redirection de port** sur ta box/routeur :
   - Port `443` (HTTPS) → `IP_NAS:443`

3. **Reverse proxy** dans DSM → Panneau de configuration → Portail de connexion → Proxy inverse :

| Champ | Valeur |
|-------|--------|
| Source — Protocole | HTTPS |
| Source — Nom d'hôte | `cra.tondomaine.fr` |
| Source — Port | `443` |
| Destination — Protocole | HTTP |
| Destination — Nom d'hôte | `localhost` |
| Destination — Port | `8080` |

4. **Certificat SSL Let's Encrypt** :
   DSM → Sécurité → Certificat → Ajouter → Let's Encrypt → `cra.tondomaine.fr`

---

## Mise à jour sur Synology

### Manuellement

```bash
# Sur le NAS via SSH
bash /volume1/docker/cra/synology-update.sh
```

### Automatiquement (planificateur DSM)

DSM → Panneau de configuration → **Planificateur de tâches** → Créer → Tâche déclenchée :

| Paramètre | Valeur |
|-----------|--------|
| Nom | `Mise à jour CRA` |
| Utilisateur | `root` |
| Planification | Hebdomadaire (ex: dimanche 3h00) |
| Commande | `bash /volume1/docker/cra/synology-update.sh` |

---

## Variables d'environnement

| Variable | Défaut | Description |
|----------|--------|-------------|
| `APACHE_RUN_USER` | `www-data` | Utilisateur Apache |
| `APACHE_RUN_GROUP` | `www-data` | Groupe Apache |

La configuration applicative se fait entièrement depuis l'interface web (pas de variables d'environnement applicatives).

---

## Structure du projet

```
cra/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php      Connexion / déconnexion
│   │   ├── CraController.php       Saisie CRA (jour, demi-journée, note, config)
│   │   ├── AdminController.php     Gestion admin (users, archives, reset)
│   │   └── TeamController.php      Équipes et membres virtuels
│   ├── Models/
│   │   ├── User.php                Utilisateurs réels et virtuels
│   │   ├── Cra.php                 Jours, demi-journées, notes, config périodes, stats
│   │   └── Team.php                Équipes et membres
│   ├── Views/
│   │   ├── auth/login.php
│   │   ├── shared/layout.php       Layout (sidebar, thème, CSRF, PWA, mobile)
│   │   ├── cra/year.php            Vue annuelle + gestion périodes de config
│   │   ├── cra/month.php           Vue mensuelle + demi-journées SVG
│   │   ├── admin/                  Vues d'administration
│   │   └── teams/                  Vues des équipes
│   └── Core/
│       ├── Router.php              Routeur HTTP
│       ├── DB.php                  PDO SQLite + migrations auto
│       └── Controller.php          Auth, CSRF, sessions, flash
├── config/config.php               BASE_URL, DB_FILE, ARCHIVES_DIR
├── data/                           BDD SQLite + archives (volume Docker)
├── public/
│   ├── index.php                   Point d'entrée unique
│   ├── .htaccess                   Réécriture + sécurité Apache
│   ├── manifest.json               PWA manifest
│   └── icons/                      Icônes PWA (192, 512, apple-touch, favicon)
├── Dockerfile
├── docker-compose.yml
├── deploy.sh                       Déploiement serveur Linux
├── update.sh                       Mise à jour depuis GitHub
├── purge.sh                        Suppression complète
└── synology-update.sh              Mise à jour sur NAS Synology
```

---

## Types de journées

| Code | Libellé | Raccourci |
|------|---------|:---------:|
| `p` | Présentiel | `P` |
| `t` | Télétravail | `T` |
| `r` | RTT | `R` |
| `c` | Congé payé | `C` |
| `s` | Sans solde | `S` |
| `f` | Jour férié | `F` |
| — | Effacer | `0` |

### Saisie des demi-journées

| Action | Résultat |
|--------|----------|
| **Clic** sur jour vide | Journée complète avec le type sélectionné |
| **Clic** sur jour plein | Change le type (ou efface si mode `0`) |
| **Double-clic** sur jour plein | Bascule en demi-journées — AM = type actuel, PM = vide |
| **Clic zone haute** (demi) | Change la partie matin |
| **Clic zone basse** (demi) | Change la partie après-midi |
| **Double-clic** sur demi | Ouvre la note du jour |
| Mode `0` + clic sur demi | Efface cette demi-journée |

Les stats comptent **0.5 jour** par demi-journée.

---

## Gestion des accès

| Rôle | Droits |
|------|--------|
| `admin` | Accès total — gestion users, équipes, délégations, archives, remise à zéro |
| `user` | Son CRA + CRA délégués en lecture + gestion de ses propres équipes |
| Virtuel | Aucun accès — CRA saisi par le responsable d'équipe |

### Configuration trajets par périodes

La distance/durée/indemnité est configurable **par période** avec une date d'effet.  
Exemple : avant déménagement (40 km), après déménagement (25 km).  
Les stats et exports utilisent automatiquement la bonne configuration pour chaque mois.

---

## Sécurité

- Mots de passe hashés **bcrypt** (`password_hash`)
- Protection **CSRF** sur tous les formulaires POST et appels AJAX
- **Whitelist** des types de journées et colonnes SQL modifiables
- Sessions avec cookie `httponly`, `samesite=Strict`, timeout 8h
- Régénération d'ID de session après login (anti-fixation)
- Délai anti-brute-force sur le login (`sleep(1)`)
- Headers HTTP : `X-Frame-Options DENY`, `X-Content-Type-Options`, `CSP`
- `ServerTokens Prod` — version Apache non exposée
- Dossier `data/` protégé `.htaccess` + permissions `700`
- Requêtes SQL toutes préparées PDO

---

## Sauvegarde et restauration

### Sauvegarde manuelle

```bash
docker run --rm \
  -v cra_cra_data:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/cra_backup_$(date +%Y%m%d).tar.gz -C /data .
```

### Restauration

```bash
docker-compose down
docker volume create cra_cra_data
docker run --rm \
  -v cra_cra_data:/data \
  -v $(pwd):/backup \
  alpine tar xzf /backup/cra_backup_YYYYMMDD.tar.gz -C /data
docker-compose up -d
```

### Archives applicatives

Admin → Archives → snapshot JSON par utilisateur ou équipe, téléchargeable.

---

## Ajouter le raccourci sur mobile (PWA)

**iOS Safari** → icône partage → "Sur l'écran d'accueil"

**Android Chrome** → menu ⋮ → "Ajouter à l'écran d'accueil"

L'app s'ouvre en mode standalone (sans barre de navigation du navigateur).

---

## Dépannage

**Container ne démarre pas**
```bash
docker-compose logs cra
```

**Erreur 500 / page blanche**
```bash
docker exec cra-app tail -50 /var/log/apache2/error.log
```

**Problème de droits sur data/**
```bash
docker exec cra-app chown -R www-data:www-data /var/www/html/data
docker exec cra-app chmod -R 700 /var/www/html/data
```

**Réinitialiser le mot de passe admin**
```bash
docker exec -it cra-app php -r "
require '/var/www/html/config/config.php';
\$pdo = new PDO('sqlite:' . DB_FILE);
\$hash = password_hash('NouveauMotDePasse', PASSWORD_DEFAULT);
\$pdo->prepare('UPDATE users SET password=? WHERE username=?')
    ->execute([\$hash, 'admin']);
echo 'Mot de passe mis à jour.' . PHP_EOL;
"
```

**Build Docker échoue (exit code 100)**
```bash
docker builder prune -f
docker-compose build --no-cache
```

---

## Licence

MIT — voir [LICENSE](LICENSE)
