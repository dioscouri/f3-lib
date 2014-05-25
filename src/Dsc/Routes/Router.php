<?php
namespace Dsc\Routes;

/**
 * Router class is used to manage all registered groups of routes for f3 apps
 *
 * @author Lukas Polak
 */
class Router
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
            $f3 = \Base::instance();
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
                            $f3->route( $route->pattern, $route->handler, $route->ttl, $route->kbps );
                        }
                    }
                }
            }
        }
    }
}