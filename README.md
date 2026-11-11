# LibreNMS Stats

Laravel 13 application for [stats.librenms.org](https://stats.librenms.org).

## Setup

Install PHP 8.3+, Composer, and the PDO driver for your database. Then:

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The default configuration uses SQLite at `database/stats.sqlite`. Set `DB_CONNECTION` and the other `DB_*` values in `.env` to use MySQL or MariaDB. Migrations load the matching schema snapshot for a new database.

For production, set `APP_ENV=production` and `APP_DEBUG=false`, point the web root to `public/`, and give the web and maintenance processes write access to `storage/` and `bootstrap/cache/`.

## Sample data

To create a test dataset, configure `.env` to point to a separate, empty database, then run:

```sh
php artisan migrate --seed
```

The seeder creates daily reports for the past three months. Set `STATS_SEED_INSTALLATIONS` in `.env` to change the number of installations.

## Maintenance

```sh
php artisan stats:cache-graphs --force
php artisan stats:device-info --os=ios --limit=30
php artisan stats:purge
```
