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

### Template Overrides

Overview of how they work:
1) Specify the overrides folder
2) Create overrides for the layouts you want to override in your overrides folder, remembering to mirror the actual filesystem path for the file you want to override

Details:
1) create an apps/Overrides/ folder to hold all of your overrides
2) add an apps/Overrides/bootstrap.php file
3) in your apps/Overrides/bootstrap.php file, set the UI_OVERRIDES variable with $f3 to the full path to your apps/Overrides/ folder, e.g.
```
$ui_overrides = $f3->get('PATH_ROOT') . "apps/Overrides/";
$f3->set('UI_OVERRIDES', $ui_overrides);
```
4) create an override for a view in your apps/Overrides folder.  For example, to override the blog/category view, you'd create this file in your overrides folder:
```
/apps/Overrides/Blog/Site/Views/posts/category.php
```
