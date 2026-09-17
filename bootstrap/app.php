<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Storage bisa dialihkan lewat env STORAGE_PATH (mis. ke Railway Volume /data
// agar foto absen permanen). Tanpa itu, Vercel serverless memakai /tmp
// (satu-satunya direktori writable) dan lokal memakai storage/ bawaan.
$storagePath = $_SERVER['STORAGE_PATH'] ?? getenv('STORAGE_PATH') ?: null;
if (! $storagePath && (isset($_SERVER['VERCEL']) || getenv('VERCEL'))) {
    $storagePath = '/tmp/laravel-storage';
    @mkdir('/tmp/views', 0755, true);
}
if ($storagePath) {
    $app->useStoragePath($storagePath);
    foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'app/public', 'logs'] as $dir) {
        @mkdir($storagePath.'/'.$dir, 0755, true);
    }
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
