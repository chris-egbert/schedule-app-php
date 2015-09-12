<?php

require __DIR__ . '/../vendor/autoload.php';

// TODO: Pull this in from config
date_default_timezone_set('America/New_York');

$app = Spark\Application::boot();

$app->setMiddleware([
    'Relay\Middleware\ResponseSender',
    'Spark\Handler\ExceptionHandler',
    'Spark\Handler\RouteHandler',
    'Spark\Handler\ActionHandler',
]);

$app->addRoutes(function(Spark\Router $r) {

    // User
    $r->get('/user[/{id}]', 'Spark\Project\Domain\User');
    $r->post('/user[/{id}]', 'Spark\Project\Domain\User');
    $r->put('/user[/{id}]', 'Spark\Project\Domain\User');
    // $r->delete('/user/{id}', 'Spark\Project\Domain\User');

    // Shift
    $r->get('/shift[/{id}]', 'Spark\Project\Domain\Shift');
    $r->post('/shift[/{id}]', 'Spark\Project\Domain\Shift');
    $r->put('/shift[/{id}]', 'Spark\Project\Domain\Shift');

    // Report
    $r->get('/report[/{report}]', 'Spark\Project\Domain\Report');

});

$app->run();
