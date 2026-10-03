<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// File bootstrap Laravel: membuat instance aplikasi dan mendaftarkan konfigurasi dasar.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Route web utama aplikasi.
        web: __DIR__.'/../routes/web.php',

        // Route command console Artisan.
        commands: __DIR__.'/../routes/console.php',

        // Endpoint health check bawaan Laravel.
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tempat menambahkan middleware global atau alias middleware jika dibutuhkan.
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tempat mengatur handling exception custom jika dibutuhkan.
        //
    })->create();
