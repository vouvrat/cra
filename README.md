# CRA — Compte Rendu d'Activité

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker)](https://docker.com)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite)](https://sqlite.org)
[![License](https://img.shields.io/badge/Licence-MIT-green)](LICENSE)

Application web de suivi d'activité professionnelle (CRA) auto-hébergée.  
Multi-utilisateurs, gestion d'équipes, délégations, thème clair/sombre.

---

## Fonctionnalités

- **Saisie journalière** — Présentiel, Télétravail, RTT, Congé payé, Sans solde, Férié
- **Vue mensuelle** avec calendrier interactif (clic = saisie, double-clic = note)
- **Vue annuelle** avec récapitulatif et calcul automatique des trajets/indemnités
- **Multi-utilisateurs** avec rôles `admin` / `user`
- **Équipes** — un responsable gère les CRA de ses membres, y compris des **comptes virtuels** (sans accès)
- **Délégations** — un utilisateur peut consulter le CRA d'un autre (lecture seule)
- **Archives** — snapshots JSON par utilisateur ou équipe, téléchargeables
- **Export CSV** par utilisateur ou par équipe
- **Thème clair / sombre** — bascule par utilisateur, persisté localement
- **Fériés français** calculés dynamiquement pour chaque année
- **Sécurité** — CSRF, sessions sécurisées, bcrypt, requêtes préparées PDO

---

## Installation rapide

### Prérequis

- Docker Engine ≥ 24
- Docker Compose plugin ≥ 2

### Déploiement

```bash
# Cloner le dépôt
git clone https://github.com/TON_USER/TON_REPO.git cra
cd cra

# Déployer
chmod +x deploy.sh
./deploy.sh
```

L'application est disponible sur `http://IP_SERVEUR:8080`

> **Compte par défaut :** `admin` / `admin123` — **à changer immédiatement** via Admin → Utilisateurs.

### Options du script de déploiement

```bash
./deploy.sh --port 9090      # Changer le port (défaut : 8080)
./deploy.sh --upgrade        # Mise à jour avec backup automatique des données
```

---

## Mise à jour depuis GitHub

```bash
# Mise à jour complète (git pull + rebuild Docker)
./update.sh

# Mise à jour rapide des fichiers PHP uniquement (sans rebuild)
./update.sh --no-rebuild
```

Le script `update.sh` :
1. Vérifie les modifications locales et propose de les écraser
2. Effectue le `git pull`
3. Sauvegarde automatiquement les données avant rebuild
4. Reconstruit l'image et redémarre le container
5. Affiche le changelog des commits appliqués

---

## Purge complète

```bash
./purge.sh
# Puis redéployer
./deploy.sh
```

---

## Structure du projet

```
cra/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php      Connexion / déconnexion
│   │   ├── CraController.php       Saisie et affichage des CRA
│   │   ├── AdminController.php     Gestion admin (users, archives, reset)
│   │   └── TeamController.php      Équipes et membres virtuels
│   ├── Models/
│   │   ├── User.php                Utilisateurs réels et virtuels
│   │   ├── Cra.php                 Jours, notes, config, stats, fériés
│   │   └── Team.php                Équipes et membres
│   ├── Views/
│   │   ├── auth/login.php
│   │   ├── shared/layout.php       Layout principal (sidebar, thème, CSRF)
│   │   ├── cra/year.php            Vue annuelle
│   │   ├── cra/month.php           Vue mensuelle avec calendrier
│   │   ├── admin/                  Vues d'administration
│   │   └── teams/                  Vues des équipes
│   └── Core/
│       ├── Router.php              Routeur HTTP
│       ├── DB.php                  PDO SQLite + migrations auto
│       └── Controller.php          Auth, CSRF, sessions, flash
├── config/config.php               Constantes (BASE_URL, DB_FILE…)
├── data/                           BDD + archives (volume Docker, non versionné)
├── public/
│   ├── index.php                   Point d'entrée unique
│   └── .htaccess                   Réécriture + sécurité Apache
├── Dockerfile
├── docker-compose.yml
├── deploy.sh                       Déploiement initial
├── update.sh                       Mise à jour depuis GitHub
└── purge.sh                        Suppression complète
```

---

## Types de journées

| Code | Libellé       | Raccourci clavier |
|------|---------------|:-----------------:|
| `p`  | Présentiel    | `P`               |
| `t`  | Télétravail   | `T`               |
| `r`  | RTT           | `R`               |
| `c`  | Congé payé    | `C`               |
| `s`  | Sans solde    | `S`               |
| `f`  | Jour férié    | `F`               |
| —    | Effacer       | `0`               |

**Clic** sur un jour = appliquer le type sélectionné · **Double-clic** = ajouter une note

---

## Gestion des accès

| Rôle       | Droits |
|------------|--------|
| `admin`    | Accès total — gestion users, équipes, délégations, archives, remise à zéro |
| `user`     | Son CRA + CRA délégués en lecture + gestion de ses propres équipes |
| Virtuel    | Aucun accès — CRA saisi par le responsable d'équipe |

### Équipes et comptes virtuels

Tout utilisateur peut créer une équipe. Il peut y ajouter :
- Des **membres virtuels** — comptes sans login, CRA saisi par le responsable
- Des **membres réels** — utilisateurs existants, vue en lecture seule

### Délégations

Admin → Délégations → *"X peut consulter le CRA de Y"*. L'utilisateur X voit le CRA de Y en lecture seule avec une bannière d'avertissement.

---

## Configuration sans Docker

### Debian / Ubuntu

```bash
apt-get install -y php8.3 php8.3-sqlite3 libapache2-mod-php8.3
a2enmod rewrite headers
```

### VirtualHost Apache

```apache
<VirtualHost *:80>
    ServerName cra.mondomaine.local
    DocumentRoot /var/www/cra/public

    <Directory /var/www/cra/public>
        AllowOverride All
        Require all granted
    </Directory>
    <Directory /var/www/cra/data>
        Require all denied
    </Directory>
</VirtualHost>
```

### Droits fichiers

```bash
chown -R root:www-data /var/www/cra
chmod -R 750 /var/www/cra
chown -R www-data:www-data /var/www/cra/data
chmod -R 700 /var/www/cra/data
```

### Sous-dossier (optionnel)

Si l'app est servie depuis `/cra/` modifier `config/config.php` :
```php
define('BASE_URL', '/cra/');
```

---

## Sécurité

- Mots de passe hashés **bcrypt** (`password_hash`)
- **Protection CSRF** sur tous les formulaires POST et appels AJAX
- **Whitelist** des types de journées et des colonnes SQL modifiables
- Sessions avec cookie `httponly`, `samesite=Strict`, timeout 8h
- Régénération d'ID de session après login (anti-fixation)
- Délai anti-brute-force sur le login (`sleep(1)`)
- Headers HTTP : `X-Frame-Options DENY`, `X-Content-Type-Options`, `CSP`
- `ServerTokens Prod` — pas de version Apache exposée
- Dossier `data/` protégé par `.htaccess` et permissions `700`
- Toutes les requêtes SQL utilisent des **requêtes préparées PDO**

---

## Sauvegarde et restauration

### Sauvegarder manuellement

```bash
docker run --rm \
  -v cra_cra_data:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/cra_backup_$(date +%Y%m%d).tar.gz -C /data .
```

### Restaurer

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

Admin → Archives → créer un snapshot JSON par utilisateur ou équipe.

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

**Problème de droits**
```bash
docker exec cra-app chown -R www-data:www-data /var/www/html/data
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

---

## Licence

MIT — voir [LICENSE](LICENSE)
