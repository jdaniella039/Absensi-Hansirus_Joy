<?php

// Konfigurasi utama aplikasi Laravel.
return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    // Nama aplikasi, biasanya diambil dari APP_NAME di file .env.
    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    // Environment aplikasi: local, production, testing, dan sebagainya.
    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    // Mode debug: true untuk development, false untuk production.
    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    // URL dasar aplikasi untuk generate link dari Artisan atau helper URL.
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    // Zona waktu default aplikasi.
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    // Bahasa utama aplikasi.
    'locale' => env('APP_LOCALE', 'en'),

    // Bahasa cadangan jika translation utama tidak ditemukan.
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    // Locale untuk data dummy Faker.
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    // Algoritma enkripsi yang dipakai Laravel.
    'cipher' => 'AES-256-CBC',

    // APP_KEY dari .env untuk enkripsi cookie, session, dan data terenkripsi lain.
    'key' => env('APP_KEY'),

    // Key lama jika aplikasi pernah rotasi APP_KEY.
    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    // Konfigurasi maintenance mode Laravel.
    'maintenance' => [
        // Driver penyimpanan status maintenance.
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),

        // Store cache/database yang dipakai jika driver maintenance membutuhkannya.
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
