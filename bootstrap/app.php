<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
       api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
       // apiPrefix: '/api' //hinzugefügt
    )
    ->withMiddleware(function (Middleware $middleware) {
      /*  //
        $middleware->alias([
            'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->append(\App\Http\Middleware\CustomizeSanctumResponses::class);
       // $middleware->redirectGuestsTo('/auth/login');*/
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
