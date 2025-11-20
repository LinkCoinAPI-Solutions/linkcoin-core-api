<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(dirname(dirname(__DIR__))).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(dirname(dirname(__DIR__))).'/.env');
}

$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

date_default_timezone_set('UTC');
ini_set('memory_limit', '1G');
