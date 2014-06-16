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
}