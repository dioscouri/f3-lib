<?php
namespace Dsc;

class Apps extends Singleton
{

    /**
     *
     * @param unknown_type $app            
     * @return \Dsc\Apps
     */
    public function bootstrap($app_name = null, $additional_paths = array())
    {
        $route = $this->app->hive()['PATH'];
        
        //\FB::error( $route );
        //\FB::log('Before bootstrapping');
        //\FB::warn(round(memory_get_usage(TRUE)/1e3,1) . ' KB');
                
        $bootstraps = array(); // array of all app bootstrap classes
        $paths = array_merge( array(), array( $this->app->get('PATH_ROOT') . 'vendor/dioscouri/', $this->app->get('PATH_ROOT') . 'apps/' ), $additional_paths );
        
        if (strpos($route, '/asset/') === 0) 
        {
            $app_name = 'f3-assets';
        }
        
        // bootstrap a single app
        if (!empty($app_name))
        {
            $app = null;
            foreach ($paths as $path) 
            {
                if (file_exists( $path . $app_name . '/bootstrap.php'))
                {
                    require_once $path . $app_name . '/bootstrap.php';
                    if (!empty($app) && is_a($app, '\Dsc\Bootstrap'))
                    {
                        $bootstraps[] = $app;
                    }
                }
            }
            
            return $this->load($bootstraps);
        }
        
        //\FB::log('Bootstrapping ALL apps');
        
        // bootstrap all apps
        if (!defined('JPATH_ROOT'))
        {
            define('JPATH_ROOT', $this->app->get('PATH_ROOT'));
        }
        
        foreach ($paths as $path)
        {
            if ($folders = \Joomla\Filesystem\Folder::folders($path))
            {
                foreach ($folders as $folder)
                {
                    $app = null;
                    if (file_exists($path . $folder . '/bootstrap.php'))
                    {
                        require_once $path . $folder . '/bootstrap.php';
                        if (!empty($app) && is_a($app, '\Dsc\Bootstrap'))
                        {
                            $bootstraps[] = $app;
                        }
                    }
                }
            }            
        }
        
        return $this->load($bootstraps);
    }

    /**
     * Registers a path so application can look for its code there
     *
     * @param $path Path            
     * @param $app Name
     *            app
     */
    public static function registerPath($path, $app)
    {
        $paths = \Base::instance()->get('dsc.' . $app . '.paths');
        if (empty($paths) || !is_array($paths))
        {
            $paths = array();
        }
        
        // if $path is not already registered, register it
        // last ones inserted are given priority by using unshift
        if (!in_array($path, $paths))
        {
            array_unshift($paths, $path);
            \Base::instance()->set('dsc.' . $app . '.paths', $paths);
        }
        
        return $paths;
    }
    
    /**
     * Loads an array of bootstap classes
     * 
     * @param array $bootstraps
     * @return \Dsc\Apps
     */
    public function load(array $bootstraps) 
    {
        //\FB::log('After bootstrapping');
        //\FB::warn(round(memory_get_usage(TRUE)/1e3,1) . ' KB');
                
        if (!empty($bootstraps))
        {
            $global_app_name = $this->app->get('APP_NAME');
            foreach ($bootstraps as $bootstrap)
            {
                $bootstrap->command('pre', $global_app_name);
            }
        
            foreach ($bootstraps as $bootstrap)
            {
                $bootstrap->command('run', $global_app_name);
            }
        
            foreach ($bootstraps as $bootstrap)
            {
                $bootstrap->command('post', $global_app_name);
            }
        }
        
        //\FB::log('After loading all apps');
        //\FB::warn(round(memory_get_usage(TRUE)/1e3,1) . ' KB');
        
        return $this;
    }
}
