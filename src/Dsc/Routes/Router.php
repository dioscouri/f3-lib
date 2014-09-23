<?php
namespace Dsc\Routes;

/**
 * Router class is used to manage all registered groups of routes for f3 apps
 *
 * @author Lukas Polak
 */
class Router extends \Dsc\Singleton
{
    private $groups = array();

    /**
     * Mounts group of routes to the router
     *
     * @param $group Object
     *            containing a group of routes
     * @param $name Name
     *            of the routing group, in case we want to request it directly
     */
    public function mount( $group, $name = '' )
    {
        if (strlen( $name ) == 0)
        {
            $name = '_all';
        }
        if (is_array( $this->groups ) == false)
        {
            $this->groups = array();
        }
        
        if (isset( $this->groups[$name] ))
        {
            $this->groups[$name][] = $group;
        }
        else
        {
            $this->groups[$name] = array(
                $group 
            );
        }
    }

    /**
     * Registers all routes from all mounted groups at once
     */
    public function registerRoutes()
    {
        if (count( $this->groups ))
        {
            foreach ( $this->groups as $group_list )
            {
                foreach ( $group_list as $group )
                {
                    $group->initialize();
                    $routes = $group->getListRoutes();
                    if (count( $routes ))
                    {
                        foreach ( $routes as $route )
                        {
                            static::route( $route->pattern, $route->handler, $route->ttl, $route->kbps );
                        }
                    }
                }
            }
        }
    }
    
    /**
     * 
     * @param unknown $pattern
     * @param unknown $handler
     * @param number $ttl
     * @param number $kbps
     */
    public static function route($pattern, $handler, $ttl=0, $kbps=0) 
    {
        if (strpos($pattern, 'GET') !== false && strpos($pattern, '[ajax]') === false)
        {
            $patterns = explode('|', $pattern);
            if (!in_array('HEAD', $patterns)) {
                array_unshift($patterns, 'HEAD');
            }
            $pattern = implode('|', $patterns);
        }
        
        $f3 = \Base::instance();
        $f3->route( $pattern, $handler, $ttl, $kbps );
    }
}