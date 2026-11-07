# LibreNMS Stats

Laravel 13 application for [stats.librenms.org](https://stats.librenms.org).

## Setup

1. Install PHP 8.3 or newer with PDO MySQL, Composer, and MySQL or MariaDB.
2. Run `composer install`, copy `.env.example` to `.env`, and run `php artisan key:generate`.
3. Set `APP_URL` and the `DB_*` settings in `.env`. For an existing installation, copy the connection settings from the old `config.php` and point Laravel at the existing database. For a new installation, create an empty database and import `schema/schema.sql` once.
4. Run `php artisan serve` for local development. In production, set `APP_ENV=production` and `APP_DEBUG=false`, point the document root at `public/`, and route requests for missing files through `public/index.php`. This includes `/data.php`, `/log.php`, and `/clear.php`, which are Laravel routes. The web and maintenance processes need write access to `storage/` and `bootstrap/cache/`.

Charts and the dashboard count use Laravel's file cache. Set `STATS_CACHE_TTL` to the desired lifetime in seconds. The default is 86400 seconds.

## Local preview with SQLite

Install PHP with PDO SQLite and use the `sqlite3` CLI to create a preview database from the project root.

```sh
sqlite3 -bail database/preview.sqlite < database/preview.sql
```

Set `DB_CONNECTION=sqlite` and `DB_DATABASE` to the absolute path of `database/preview.sqlite` in `.env`. Set `CACHE_STORE=array` to read fresh preview data on each request. Run `php artisan serve` and open [http://localhost:8000](http://localhost:8000).

The seed uses dates relative to when it runs. To refresh the sample data after the recent charts age out, create a fresh SQLite file using the same SQL script and point `.env` at it.

## Maintenance

```sh
php artisan stats:cache-graphs --force
php artisan stats:device-info --os=ios --limit=30
php artisan stats:purge
```

The purge command removes all expired runs and their chart data in batches.
