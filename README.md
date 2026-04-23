# AssistAmbu — API

> API REST — Laravel 11 · PHP 8.3 · Sanctum

## Prérequis

- PHP 8.3+
- Composer
- MySQL 8 (via Docker)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Variables d'environnement

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assistambu
DB_USERNAME=assistambu
DB_PASSWORD=assistambu

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@assistambu.fr
MAIL_FROM_NAME="AssistAmbu"
```

## Endpoints auth

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| POST | `/api/auth/register` | Non | Inscription |
| POST | `/api/auth/login` | Non | Connexion |
| POST | `/api/auth/logout` | Oui | Déconnexion |
| GET | `/api/auth/me` | Oui | Utilisateur connecté |
| POST | `/api/auth/forgot-password` | Non | Lien reset password |
| POST | `/api/auth/reset-password` | Non | Reset password |
| POST | `/api/auth/email/verify/send` | Oui | Envoyer email vérification |
| GET | `/api/auth/email/verify/{id}/{hash}` | Oui | Vérifier email |

## Conventions Git

Format : `type(scope): message en français`

Types : `feat` `fix` `chore` `refactor` `style` `docs` `test`

Branches : `main` → `develop` → `feat/*` / `fix/*`