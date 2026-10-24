# Kalaat Bona FC

Site et espace de gestion d'un club de football (académie) : site vitrine, formulaires d'inscription, portail joueur/parent, back-office admin, coachs, coordinateur et finances.

## Stack

- PHP 8.1+ (PDO, sessions, password_hash)
- MariaDB / MySQL (utf8mb4)
- HTML, CSS et JavaScript natifs
- PHPMailer (inclus dans `PHPMailer/`) pour les emails SMTP
- Apache (XAMPP/WAMP) avec `mod_rewrite`

## Fonctionnalités

- Site public : accueil, programmes, inscription, contact, pages 404
- Inscription en ligne avec création du joueur, du parent et de la demande
- Portail de connexion joueur / parent
- Tableaux de bord admin, coach, coordinateur et finances
- Gestion des joueurs, parents, programmes, inscriptions, paiements et messages
- Emails d'acceptation / refus (SMTP configurable depuis l'admin)
- Multilingue FR / EN / AR (via `js/ui-manager.js`)
- Uploads protégés (exécution PHP bloquée dans `uploads/`)

## Structure

```text
admin/        Back-office (admins, coachs, coordinateur, finances)
api/          Endpoints JSON (inscriptions, contact, programmes, messages)
config/       Configuration application et base de données
database/     schema.sql + seed.sql (aucune donnée personnelle)
fonts/        Polices auto-hébergées
js/           Interface multilingue et interactions
lib/          Connexion DB, auth, sessions, mailer, layout
uploads/      Fichiers envoyés par les utilisateurs (non versionnés)
PHPMailer/    Bibliothèque d'envoi d'emails
```

## Installation locale (XAMPP)

1. Copier le projet dans `C:\xampp\htdocs\clubf` (ou adapter le chemin).
2. Démarrer Apache et MySQL.
3. Créer une base de données, par exemple `tilbi_academy`, en `utf8mb4_general_ci`.
4. Importer `database/schema.sql`, puis `database/seed.sql` dans phpMyAdmin.
5. Configurer la connexion dans `config/db.php` ou créer `config/db.local.php` :

```php
<?php
return [
    'host' => '127.0.0.1',
    'name' => 'tilbi_academy',
    'user' => 'root',
    'pass' => '',
];
```

6. Vérifier `base_path` dans `config/app.php` :
   - projet dans `htdocs/clubf` : `/clubf`
   - servi à la racine du domaine : `""`
7. Définir un code d'invitation admin dans `config/app.php` (`admin_invite_code`), ou via la variable d'environnement `ADMIN_INVITE_CODE`.
8. Ouvrir `http://localhost/clubf/admin/register` pour créer le premier admin, puis se connecter sur `http://localhost/clubf/admin/login`.
9. Configurer SMTP depuis l'admin (`Paramètres`) pour activer l'envoi des emails.

Le site doit être lancé via Apache, pas en ouvrant directement les fichiers PHP.

## Configuration

Les variables d'environnement suivantes sont supportées :

| Variable | Valeur par défaut |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `tilbi_academy` |
| `DB_USER` | `root` |
| `DB_PASS` | *(vide)* |
| `DB_CHARSET` | `utf8mb4` |
| `APP_BASE_PATH` | `/clubf` |
| `ADMIN_INVITE_CODE` | `CHANGE_ME` |

Les fichiers `config/db.local.php` et `config/app.local.php` sont ignorés par Git et permettent de garder les identifiants locaux hors du dépôt.

## Sécurité

- Ne jamais committer d'identifiants SMTP, de dump SQL réel ou de fichiers `.env`.
- Les uploads utilisateurs ne sont pas versionnés ; le dossier `uploads/` doit être accessible en écriture par PHP.
- En production : utiliser HTTPS, un mot de passe DB dédié, un code d'invitation admin fort et des sauvegardes régulières.
- Le mot de passe SMTP est stocké dans la table `settings` (modifiable depuis l'admin), pas dans le code.

## Licence

Projet privé du club Kalaat Bona FC. Ajoutez une licence si vous souhaitez publier le code publiquement.
