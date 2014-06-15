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
        
        if (strpos($route, '/asset/') === 0) 
        {
            $app_name = 'f3-assets';
        }
        
        if (!empty($app_name))
        {
            // TODO Use this array for NORMAL bootstrapping too
            $paths = array_merge( array(), array( $this->app->get('PATH_ROOT') . 'vendor/dioscouri/', $this->app->get('PATH_ROOT') . 'apps/' ), $additional_paths );
            
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
        
        // bootstrap all apps
        // loop through each child folder (only 1st level) of the /apps folder
        // if a bootstrap.php file exists, require it once
        $f3 = \Base::instance();
        if (!defined('JPATH_ROOT'))
        {
            define('JPATH_ROOT', $f3->get('PATH_ROOT'));
        }
                         
        // do the original apps first
        $path = $f3->get('PATH_ROOT') . 'vendor/dioscouri/';
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
        
        // then do the custom apps
        $path = $f3->get('PATH_ROOT') . 'apps/';
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
        
        // then do any additional paths
        foreach ($additional_paths as $additional_path)
        {
            if ($folders = \Joomla\Filesystem\Folder::folders($additional_path))
            {
                
                foreach ($folders as $folder)
                {
                    $app = null;                    
                    if (file_exists($additional_path . $folder . '/bootstrap.php'))
                    {
                        require_once $additional_path . $folder . '/bootstrap.php';
                        if (!empty($app) && is_a($app, '\Dsc\Bootstrap'))
                        {
                            $bootstraps[] = $app;
                        }
                    }
                }
            }
        }
        
        $this->load($bootstraps);
        
        return $this;
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
            foreach ($bootstraps as $key=>$bootstrap)
            {     
                if (empty($bootstrap) || !is_a($bootstrap, '\Dsc\Bootstrap')) 
                {
                	unset($bootstraps[$key]);
                }
            }
            
            $global_app_name = $this->app->get('APP_NAME');
            foreach ($bootstraps as $bootstrap)
            {
                //\FB::log('APPS-PRE: ', $bootstrap->name() );
                $bootstrap->command('pre', $global_app_name);
            }
        
            foreach ($bootstraps as $bootstrap)
            {
                //\FB::log('APPS-RUN: ', $bootstrap->name() );
                $bootstrap->command('run', $global_app_name);
            }
        
            foreach ($bootstraps as $bootstrap)
            {
                //\FB::log('APPS-POST: ', $bootstrap->name() );
                $bootstrap->command('post', $global_app_name);
            }
        }
        
        //\FB::log('After loading all apps');
        //\FB::warn(round(memory_get_usage(TRUE)/1e3,1) . ' KB');
        
        return $this;
    }
}
