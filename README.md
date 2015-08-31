f3-lib
======
A library for the F3 framework [a link](https://github.com/bcosca/fatfree), The library is designed to use fatfree as a strong base, and add on top it the files and functions to quickly create applications. There is a strong community of apps, for solving common problems. 


### The Library is standalone and be quickly added to your repo using composer

```
Add this to your project's composer.json file:

{
    "require": {
        "dioscouri/f3-lib": "dev-master"
    }
}
```

Then add the following two lines to your index.php file, immediately before $app->run();

```
// bootstap each mini-app
\Dsc\Apps::instance()->bootstrap();

// trigger the preflight event
\Dsc\System::instance()->preflight(); 
``` 


### Example index.php file

```php
<?php
//AUTOLOAD all your composer libraries now.
(@include_once (__dir__ . '/../vendor/autoload.php')) OR die("You need to run php composer.phar install for your application to run.");
//Require FatFree Base Library https://github.com/bcosca/fatfree
$app = Base::instance();
//Set the PATH so we can use it in our apps
$app->set('PATH_ROOT', __dir__ . '/../');
//This autoload loads everything in apps/* and 
$app->set('AUTOLOAD',  $app->get('PATH_ROOT') . 'apps/;');
//load the config files for enviroment
require $app->get('PATH_ROOT') . 'config/config.php';
//SET the "app_name" or basically the instance so we can server the admin or site from same url with different loaded classes
$app->set('APP_NAME', 'site');
if (strpos(strtolower($app->get('URI')), $app->get('BASE') . '/admin') !== false)
{
    $app->set('APP_NAME', 'admin');
    //stupid javascript bugs with debug off
    $app->set('DEBUG', 1);
}
// bootstap each mini-app  these are in apps folder, as well as in vender/dioscouri
\Dsc\Apps::instance()->bootstrap();
// load routes; Routes are defined by their own apps in the Routes.php files
\Dsc\System::instance()->get('router')->registerRoutes();
// trigger the preflight event PreSite, PostSite etc
\Dsc\System::instance()->preflight();
//excute everything.
$app->run();
```


### apps 
The library is designed to bootstrap "apps" apps are just a group of code that bundles features, example, f3-blog will give you a simple blogging platform to your application. 

Your, Theme, and custom site would be built to your needs as custom apps. 

a default folder structure is like this 

/apps
/config
/public
/tmp

your index.php files lives in public, as public is the only web accessible folder. 

[TODO ]
detail how to build custom apps


### THEME
DETAIL CUSTOM THEMES


### WEB SERVERS
add configs to NGINX AND APACHE






