<?php

use Slim\App;
use App\Controllers\DomainController;

return function (App $app) {
    // Source Domains
    $app->get('/api/sources', [DomainController::class, 'listSources']);
    $app->post('/api/sources', [DomainController::class, 'addSource']);
    $app->delete('/api/sources/{id}', [DomainController::class, 'deleteSource']);

    // Target Domains
    $app->get('/api/targets', [DomainController::class, 'listTargets']);
    $app->post('/api/targets', [DomainController::class, 'addTarget']);
    $app->delete('/api/targets/{id}', [DomainController::class, 'deleteTarget']);

    // Domain Rules
    $app->get('/api/rules', [DomainController::class, 'listRules']);
    $app->post('/api/rules', [DomainController::class, 'addRule']);
    $app->put('/api/rules/{id}', [DomainController::class, 'updateRule']);
    $app->delete('/api/rules/{id}', [DomainController::class, 'deleteRule']);

    // Main redirect handler
    $app->get('/', [DomainController::class, 'handleRedirect']);
};
