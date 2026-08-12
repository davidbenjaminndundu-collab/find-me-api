# Find Me API

Backend Laravel du projet Find Me, marketplace de services numériques.

## Stack technique

- Laravel 12
- PHP 8.4 via Docker
- PostgreSQL
- Redis
- Docker Compose
- GitHub Actions

## Prérequis

- Docker Desktop
- Git
- Node.js
- npm

## Installation locale

```bash
git clone <url-du-repo>
cd find-me-api
cp .env.example .env
docker compose up --build

## dans un autre terminal 
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate 