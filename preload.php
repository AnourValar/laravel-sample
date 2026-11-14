<?php

/**
 * APP_ENV=production
 * APP_DEBUG=false
 * CACHE_STORE=redis
 * QUEUE_CONNECTION=redis
 *
 * composer install --optimize-autoloader --classmap-authoritative --no-dev
 * php artisan optimize
 *
 * opcache, jit enable // if benchmark
 *
 * get_included_files()
 *  \r =>
 *  ^\s+\d+ \=. "/var/www/html/code/ =>    __DIR__.'/
 *  " =>',
 *
 * php artisan inspire // clean up all warnings
 */
if (! file_exists(__DIR__.'/bootstrap/cache/config.php')) {
    return; // для совместимости с деплой
}

/* -- START -- */
$filesCompile = [
    __DIR__.'/vendor/autoload.php',
];
/* -- FINISH -- */

foreach ($filesCompile as $file) {
    if (! opcache_compile_file($file)) {
        throw new Exception('Cannot compile: '.$file);
    }
}
