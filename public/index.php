<?php

declare(strict_types=1);

use App\Bootstrap\Container;
use App\Modules\Validation\Exceptions\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Create Container (This handles ENV loading and AdminConfigDTO)
$container = Container::create();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

/**
 * 🔒 REQUIRED — Canonical
 * Enables JSON parsing for application/json
 */
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(
    true,   // displayErrorDetails (dev فقط)
    false,  // logErrors
    false   // logErrorDetails
);

$errorMiddleware->setErrorHandler(
    ValidationFailedException::class,
    function (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app): ResponseInterface {

        /** @var ValidationFailedException $exception */
        $payload = json_encode(
            [
                'error' => 'Invalid request payload',
                'errors' => $exception->getErrors()
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $app->getResponseFactory()->createResponse(422);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }
);


// Register Routes
$routes = require __DIR__ . '/../routes/web.php';
$routes($app);

// Run App
$app->run();
