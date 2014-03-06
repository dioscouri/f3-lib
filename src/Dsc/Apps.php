<?php 
namespace Dsc;

class Apps extends Singleton
{
    /**
     * 
     * @param unknown_type $app
     * @return \Dsc\Apps
     */
    public function bootstrap($app=null, $additional_paths=array())
    {
        if (!empty($app)) {
            // bootstrap just a single app from the /apps folder
            $path = $f3->get('PATH_ROOT') . 'apps/';
            if (file_exists( $path . $app . '/bootstrap.php' )) {
                require_once $path . $app . '/bootstrap.php';
                if( isset( $app ) ){
                 	$apps []= $app;                    	
                }
            }            
            return $this;
        }
        
        // bootstrap all apps
        // loop through each child folder (only 1st level) of the /apps folder
        // if a bootstrap.php file exists, require it once
        $f3 = \Base::instance();
        if (!defined('JPATH_ROOT')) {
            define('JPATH_ROOT', $f3->get('PATH_ROOT'));
        }
        
        $apps = array(); // array of all apps
        
		// do the original apps first        
        $path = $f3->get('PATH_ROOT') . 'vendor/dioscouri/';
        if ($folders = \Joomla\Filesystem\Folder::folders( $path ))
        {
            foreach ($folders as $folder)
            {
                if (file_exists( $path . $folder . '/bootstrap.php' )) {
                    require_once $path . $folder . '/bootstrap.php';
                    if( isset( $app ) ){
                    	$apps []= $app;                    	
                    }
                }
            }
        }
        
        // then do the custom apps 
        $path = $f3->get('PATH_ROOT') . 'apps/';
        if ($folders = \Joomla\Filesystem\Folder::folders( $path ))
        {
        	foreach ($folders as $folder)
        	{
        		if (file_exists( $path . $folder . '/bootstrap.php' )) {
        			require_once $path . $folder . '/bootstrap.php';
        		    if( isset( $app ) ){
                    	$apps []= $app;                    	
                    }
        		}
        	}
        }
        
        // then do any additional paths
        foreach ($additional_paths as $additional_path) 
        {
        	if ($folders = \Joomla\Filesystem\Folder::folders( $additional_path ))
        	{
        		foreach ($folders as $folder)
        		{
        			if (file_exists( $additional_path . $folder . '/bootstrap.php' )) {
        				require_once $additional_path . $folder . '/bootstrap.php';
        			    if( isset( $app ) ){
                    		$apps []= $app;                    	
                    	}
        			}
        		}
        	}        	
        }
        
        // now let's run all the apps
        if( count( $apps ) > 0 ){
			$global_app_name = $f3->get('APP_NAME');
        	foreach( $apps as $app ){
        		$app->command( 'pre', $global_app_name );
        	}

            foreach( $apps as $app ){
        		$app->command( 'run', $global_app_name );
        	}

            foreach( $apps as $app ){
        		$app->command( 'post', $global_app_name );
        	}
        }
        
        return $this;
    }
}