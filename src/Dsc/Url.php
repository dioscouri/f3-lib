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
    		$string = explode('?', $_SERVER['REQUEST_URI'])[0];
    	}
    	$full = $url->app->get('SCHEME') . "://" . $url->app->get('HOST') . $url->app->get('BASE')  . $string;
    	
    	return $full;
    }
    
    /**
     * 
     * @return string
     */
    public static function domain()
    {
        $url = static::instance();
        
        if ($domain = $url->app->get('DOMAIN')) 
        {
            return $domain;
        }
    
        $pieces = parse_url($url::base());
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) 
        {
            return $regs['domain'];
        }
        
        return $url->app->get('HOST');        
    }
    
    /**
     * Returns the base URL for the site, including the trailing slash
     *
     * @return string
     */
    public static function path()
    {
        $url = static::instance();
    
        $path = $url->app->get('PATH');
    
        return $path;
    }
}
