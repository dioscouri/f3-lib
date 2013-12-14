<?php 
namespace Dsc;

class Apps extends Object
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
        
		// do the original apps first        
        $path = $f3->get('PATH_ROOT') . 'vendor/dioscouri/';
        if ($folders = \Joomla\Filesystem\Folder::folders( $path ))
        {
            foreach ($folders as $folder)
            {
                if (file_exists( $path . $folder . '/bootstrap.php' )) {
                    require_once $path . $folder . '/bootstrap.php';
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
        			if (file_exists( $path . $folder . '/bootstrap.php' )) {
        				require_once $path . $folder . '/bootstrap.php';
        			}
        		}
        	}        	
        }
        
        return $this;
    }
}