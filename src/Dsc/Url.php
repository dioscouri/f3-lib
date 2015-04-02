<?php 
namespace Dsc;

class Url extends Singleton 
{
    use \Dsc\Traits\Meta;
    
    /**
     * Returns the base URL for the site, including the trailing slash
     * 
     * @return string
     */
    public static function base()
    {
        $url = static::instance();
        
        $base = $url->app->get('SCHEME') . "://" . $url->app->get('HOST') . $url->app->get('BASE') . "/";
        
        return $base;
    }
    
    public static function isSecure()
    {
        $url = static::instance();
        
        if ($url->app->get('SCHEME') == 'https') 
        {
            return true;
        }
        
        return false;
    }
    
     /**
     * Returns the full URL for the request
     *
     * @return string
     */
    public static function full($get = true)
    {
    	$url = static::instance();
    
    	if($get) {
    		$string = $_SERVER['REQUEST_URI'];	
    	} else {
    		$string =explode('?', $_SERVER['REQUEST_URI'])[0];
    	}
    	$full = $url->app->get('SCHEME') . "://" . $url->app->get('HOST') . $url->app->get('BASE')  . $string;
    	
    	return $full;
    }
}
