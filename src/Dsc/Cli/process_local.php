<?php
define('PATH_ROOT', realpath( __dir__ . '/../../../../../../' ) . '/' );
//AUTOLOAD all your composer libraries now.
(@include_once ( realpath( __dir__ . '/../../../../../../' ) . '/vendor/autoload.php')) OR die("You need to run php composer.phar install for your application to run.");
//Require FatFree Base Library https://github.com/bcosca/fatfree
$app = Base::instance();


$app->set('PATH_ROOT', PATH_ROOT);
$app->set('AUTOLOAD', $app->get('PATH_ROOT') . 'apps/;');

//require $app->get('PATH_ROOT') . 'vendor/autoload.php';

$app->set('APP_NAME', 'cli');

require $app->get('PATH_ROOT') . 'config/config.php';

// bootstap each mini-app
\Dsc\Apps::instance()->bootstrap();

// process the queue!
\Dsc\Queue::process('local');