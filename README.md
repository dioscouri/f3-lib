f3-lib
======
A library for the F3 framework

### Getting Started

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

