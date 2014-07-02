<?php
define('PATH_ROOT', realpath( __dir__ . '/../../../../../../' ) . '/' );
$app = require( PATH_ROOT . 'vendor/bcosca/fatfree/lib/base.php');
$app->set('PATH_ROOT', PATH_ROOT);
$app->set('AUTOLOAD', $app->get('PATH_ROOT') . 'apps/;');

require $app->get('PATH_ROOT') . 'vendor/autoload.php';

$app->set('APP_NAME', 'cli');

require $app->get('PATH_ROOT') . 'config/config.php';

// for CLI apps:
$app->set('LOGS', $app->get('PATH_ROOT') . 'logs/');
$app->set('TEMP', $app->get('PATH_ROOT') . 'tmp/');
$app->set('db.jig.dir', $app->get('PATH_ROOT') . 'jig/');

// bootstap each mini-app
\Dsc\Apps::instance()->bootstrap();

// process the queue!
\Dsc\Queue::process();