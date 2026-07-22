<?php

use App\Console\Commands\MaracujaDoctorCommand;
use App\Console\Commands\MaracujaDatabaseBackupCommand;
use App\Console\Commands\MaracujaMediaAuditCommand;
use App\Console\Commands\MaracujaMediaMigrateCommand;
use App\Console\Commands\SendPendingAudienceMessagesCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        MaracujaDoctorCommand::class,
        MaracujaDatabaseBackupCommand::class,
        MaracujaMediaAuditCommand::class,
        MaracujaMediaMigrateCommand::class,
        SendPendingAudienceMessagesCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/brevo/audience/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
