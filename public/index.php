<?php

declare(strict_types=1);

use App\Bootstrap\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Create Container
$container = Container::create();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Register Routes
$routes = require __DIR__ . '/../routes/web.php';
$routes($app);

// Run App
$app->run();
