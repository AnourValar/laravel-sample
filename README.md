# Laravel-Sample

## Specification

- [Laravel 13](https://laravel.com/docs/13.x) (stateless)
- PHP 8.5 (official docker image extensions + pdo_pgsql / redis / imagick / gd / zip / bcmath / pcntl / intl)
- PostgreSQL 18.3 (+ pg_trgm)
- Redis 8.6
- Nginx 1.30


## Debug

- Clockwork (chrome extension or /clockwork/ uri)


## i18n

Export untranslated:
```
php artisan interpreter:export en
```

Import untranslated:
```
php artisan interpreter:import en
```
