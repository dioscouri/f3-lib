<?php
namespace Dsc;

/**
 * This is base bootstrap class for every f3 application enabling us to hook up some services before
 * the real bootstrapping occurs
 */
abstract class Bootstrap extends \Dsc\Singleton
{

    protected $namespace = '';

    /**
     * The full path to the app's bootstrap.php file
     * 
     * @var string
     */
    protected $dir = '';

    /**
     * The full path to the folder containing the app's namespaced files
     * 
     * @var string
     */
    protected $base = '';

    public function __construct()
    {
        if (empty($this->base))
        {
            $this->base = $this->dir . '/src/' . $this->namespace;
        }
        
        static::registerApp($this->namespace, $this->base);
    }

    /**
     *
     * @param unknown $app            
     * @param unknown $path            
     * @return Ambigous <array, multitype:unknown >
     */
    public static function registerApp($app, $path)
    {
        $apps = (array) \Base::instance()->get('dsc.apps');
        if (empty($apps) || !is_array($apps))
        {
            $apps = array();
        }
        
        // if $apps is not already registered, register it
        if (!array_key_exists($app, $apps))
        {
            $apps[$app] = $path;
            \Base::instance()->set('dsc.apps', $apps);
        }
        
        return $apps;
    }

    /**
     * Triggers a command for a specific part of application
     *
     * @param $name Name
     *            of command
     * @param $app Name
     *            of part of application
     */
    public function command($name, $app)
    {
        $app = ucwords($app);
        if (method_exists($this, $name . $app))
        {
            $func = $name . $app;
            $this->$func();
        }
        else 
            if (method_exists($this, $name))
            {
                $this->$name($app);
            }
    }

    protected function run($app)
    {
        // handle other types of application, if no specific function defined
    }

    protected function runAdmin()
    {
        $this->runBase('Admin');
    }

    protected function runSite()
    {
        $this->runBase('Site');
    }

    /**
     * This part is common for all running all parts of application (both Admin and Site)
     *
     * @param $app Name
     *            of the part of application
     */
    protected function runBase($app)
    {
        $f3 = \Base::instance();
        $router = "\\" . $this->namespace . "\\Routes";
        if (!class_exists($router))
        {
            $router = "\\" . $this->namespace . "\\" . $app . "\\Routes";
            if (!class_exists($router))
            {
                $router = '';
            }
        }
        
        if (strlen($router))
        {
            // register all the routes
            \Dsc\System::instance()->get('router')->mount(new $router(), $this->namespace);
        }
        
        $listener = "\\" . $this->namespace . "\\Listener";
        if (class_exists($listener))
        {
            // register event listener
            \Dsc\System::instance()->getDispatcher()->addListener($listener::instance());
        }
        
        $listener = "\\" . $this->namespace . "\\" . $app . "\\Listener";
        if (class_exists($listener))
        {
            // register event listener
            \Dsc\System::instance()->getDispatcher()->addListener($listener::instance());
        }
        
        $this->registerModules($app);
        $this->registerViewFiles($app);
    }

    /**
     * This method takes care of registration all view files
     *
     * @param $app Name
     *            of the part of application
     */
    protected function registerViewFiles($app)
    {
        $f3 = \Base::instance();
        
        // register this app's view files with the theme
        if (file_exists($this->dir . '/src/' . $this->namespace . '/' . $app . '/Views/'))
        {
            \Dsc\System::instance()->get('theme')->registerViewPath($this->dir . '/src/' . $this->namespace . '/' . $app . '/Views/', $this->namespace . '/' . $app . '/Views');
        }
        else
        {
            if (file_exists($this->dir . '/src/' . $this->namespace . '/Views/'))
            {
                \Dsc\System::instance()->get('theme')->registerViewPath($this->dir . '/src/' . $this->namespace . '/Views/', $this->namespace . '/Views');
            }
        }
        
        // register the app's shared views
        \Dsc\System::instance()->get('theme')->registerViewPath($this->dir . '/src/' . $this->namespace . '/Views/', $this->namespace . '/Views');
    }

    /**
     * This method takesccase of registration all modules
     *
     * @param $app Name
     *            of the part of application
     */
    protected function registerModules($app)
    {
        if (!class_exists('\Modules\Factory'))
        {
            return;
        }
        
        // register the modules path, if you can
        $modules_path = $this->dir . "/src/" . $this->namespace . "/Modules/";
        if (!file_exists($modules_path))
        {
            // let's try more specific route
            $modules_path = $this->dir . "/src/" . $this->namespace . '/' . $app . "/Modules/";
            if (!file_exists($modules_path))
            { // not even here? maybe more luck next time
                $modules_path = '';
            }
        }
        
        if (strlen($modules_path))
        {
            \Modules\Factory::registerPath($modules_path);
        }
    }

    protected function pre($app)
    {
        // handle other types of application, if no specific function defined
    }

    protected function preAdmin()
    {}

    protected function preSite()
    {}

    protected function post($app)
    {
        // handle other types of application, if no specific function defined
    }

    protected function postAdmin()
    {}

    protected function postSite()
    {}
    
    /**
     * What's the name of this app?
     */
    public function name() 
    {
    	return $this->namespace;
    }
}