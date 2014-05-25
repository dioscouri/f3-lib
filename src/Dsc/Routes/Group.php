<?php
namespace Dsc\Routes;

/**
 * Group class is used to keep track of a group of routes with similar aspects (the same controller, the same f3-app and etc)
 *
 * @author Lukas Polak
 */
abstract class Group
{
    protected $default_params;
    protected $routes = array(); // initialized routes
    function __construct()
    {
        $this->default_params = array(
            'controller' => '',
            'action' => '',
            'namespace' => '',
            'url_prefix' => '',
        	'kbps' => 0,
        	'ttl' => 0,
        );
    }

    /**
     * Initializes all routes for this group
     *
     * NOTE: This method should be overriden by every group
     */
    abstract public function initialize();

    /**
     * Sets default parameters
     *
     * @param
     *            $params
     */
    public function setDefaults( $params )
    {
        $this->default_params = array_merge( $this->default_params, $params );
    }

    /**
     * Adds a route to the group
     *
     * @param $route String
     *            representation of the route
     * @param $request_type Request
     *            type (POST/PUT/GET/DELETE)
     * @param $params Parameters
     *            of the route (operation, only_AJAX)
     *            
     */
    public function add( $route, $request_type, $params = array() )
    {
        $orig_params = array(
            'ajax' => false,
            'namespace' => '',
            'controller' => '',
            'action' => '', 
        	'kbps' => 0,
        	'ttl' => 0,
        );
        
        $params = array_merge( $orig_params, $params );
        
        $this->routes[] = array(
            'route' => $route,
            'type' => $request_type,
            'params' => $params 
        );	
    }

    /**
     * Adds CRUD routes for both, list and item operations in the controller
     *
     * @param $plural Controller
     *            list
     * @param $singular Controller
     *            item
     * @param $plural_params Custom
     *            for list routes
     * @param $singular_params Custom
     *            for item routes
     */
    public function addCrudGroup( $plural, $singular, $plural_params = array(), $singular_params = array() )
    {
        $this->addCrudItem( $singular, $singular_params );
        $this->addCrudList( $plural, $plural_params );
    }

    /**
     * Adds CRUD item routes for selected controller
     *
     * @param $controller Name
     *            of controller
     * @param $params Parameters
     *            of the route
     *            
     */
    public function addCrudItem( $controller, $params = array() )
    {
        $orig_params = array(
            'url_prefix' => '',
            'rest_actions' => false,
            'exclude' => array() 
        );
        $params = $params + $orig_params;
        if (strlen( $params['url_prefix'] ) == 0)
        { // use controller name as fallback option
            $params['url_prefix'] = '/' . strtolower( $controller );
        }
        
        // this array defines parameters for all CRUD operations for an item
        $operation_list = array(
            'add' => array(
                array(
                    'action' => 'add',
                    'request' => 'POST',
                    'route' => '/create' 
                ) 
            ),
            'create' => array(
                array(
                    'action' => 'create',
                    'request' => 'GET',
                    'route' => '/create' 
                ) 
            ),
            'read' => array(
                array(
                    'action' => 'read',
                    'request' => 'GET',
                    'route' => '/read/@id' 
                ) 
            ),
            'edit' => array(
                'action' => 'edit',
                'request' => 'GET',
                'route' => '/edit/@id' 
            ),
            'update' => array(
                array(
                    'action' => 'update',
                    'request' => 'POST',
                    'route' => '/edit/@id' 
                ) 
            ),
            'delete' => array(
                array(
                    'action' => 'delete',
                    'request' => array(
                        'GET',
                        'DELETE' 
                    ),
                    'route' => '/delete/@id' 
                ) 
            ) 
        );
        if ((bool) $params['rest_actions'])
        {
            $operation_list['add'][] = array(
                'action' => 'add',
                'request' => 'PUT',
                'route' => '' 
            );
            
            $operation_list['read'][] = array(
                'action' => 'read',
                'request' => 'GET',
                'route' => '/@id' 
            );
            
            $operation_list['update'][] = array(
                'action' => 'update',
                'request' => 'POST',
                'route' => '/@id' 
            );
            
            $operation_list['delete'][] = array(
                'action' => 'delete',
                'request' => 'DELETE',
                'route' => '/@id' 
            );
        }
        
        $available_operations = array_keys( $operation_list );
        $operations = array_diff( $available_operations, (array) $params['exclude'] );
        $routes = array();
        foreach ( $operations as $op )
        {
            $routes[] = $operation_list[$op];
        }
        
        // add all routes you can
        if (count( $routes ))
        {
            $this->addBulkRoutes( $routes, $controller, $params['url_prefix'] );
        }
    }

    /**
     * Adds CRUD list routes for selected controller
     *
     * @param $controller Name
     *            of controller
     * @param $params Parameters
     *            of the route (url_prefix is required)
     *            
     */
    public function addCrudList( $controller, $params = array() )
    {
        $orig_params = array(
            'url_prefix' => '',
            'exclude' => array(),
            
            'datatable_links' => false,
            'get_parent_link' => false,
            'pagination_list' => true 
        );
        $params = $params + $orig_params;
        if (strlen( $params['url_prefix'] ) == 0)
        { // use controller name as fallback option
            $params['url_prefix'] = '/' . strtolower( $controller );
        }
        
        // this array defines parameters for all CRUD operations for an item
        $operation_list = array(
            'list' => array(
                array(
                    'action' => 'index',
                    'request' => array(
                        'GET',
                        'POST' 
                    ),
                    'route' => '' 
                ) 
            ),
            'delete' => array(
                'action' => 'delete',
                'request' => array(
                    'GET',
                    'POST' 
                ),
                'route' => '/delete' 
            ) 
        );
        
        if ((bool) ($params['pagination_list']))
        {
            $operation_list['list'][] = array(
                'action' => 'index',
                'request' => array(
                    'GET',
                    'POST' 
                ),
                'route' => '/page/@page' 
            );
        }
        
        $available_operations = array_keys( $operation_list );
        $operations = array_diff( $available_operations, (array) $params['exclude'] );
        $routes = array();
        foreach ( $operations as $op )
        {
            $routes[] = $operation_list[$op];
        }
        
        if ((bool) ($params['datatable_links']))
        {
            $routes[] = array(
                'action' => 'getDatatable',
                'ajax' => true,
                'request' => 'GET',
                'route' => '' 
            );
        }
        
        if ((bool) $params['get_parent_link'])
        {
            $routes[] = array(
                'action' => 'getAll',
                'ajax' => true,
                'request' => 'GET',
                'route' => '/all' 
            );
        }
        
        // add all routes you can
        if (count( $routes ))
        {
            $this->addBulkRoutes( $routes, $controller, $params['url_prefix'] );
        }
    }

    /**
     * Adds routes for settings
     *
     * @param $url_prefix Prefix
     *            of URL
     *            
     */
    public function addSettingsRoutes( $url_prefix = '' )
    {
        $routes = array(
            array(
                'action' => 'index',
                'request' => 'GET',
                'route' => '/settings' 
            ),
            array(
                'action' => 'save',
                'request' => 'POST',
                'route' => '/settings' 
            ) 
        );
        
        // add all routes you can
        $this->addBulkRoutes( $routes, 'Settings', $url_prefix );
    }

    /**
     * Adds routes in bulk
     *
     * @param $routes_list List
     *            of routes to add
     * @param $controller Controller
     *            for all added routes
     * @param $url_prefix Prefix
     *            for all added routes
     */
    private function addBulkRoutes( $routes_list, $controller, $url_prefix, $params = array() )
    {
        foreach ( $routes_list as $routes )
        {
            // consider multiple routes for the same operation
            if (! isset( $routes[0] ))
            {
                $routes = array(
                    $routes 
                );
            }
            
            foreach ( $routes as $route )
            {
                $ajax = isset( $route['ajax'] ) ? (bool) $route['ajax'] : false;
                $this->add( $url_prefix . $route['route'], $route['request'], array(
                    'controller' => $controller,
                    'action' => $route['action'],
                    'ajax' => $ajax 
                ) + $params );
            }
        }
    }
    /**
     * Adds routes for changing a state of an item in list view (EnablableItem trait)
     *
     * @param $controller 	Name of controller
     * @param $url_prefix 	Prefix of URL
     *
     */
    public function addChangeStateListRoutes( $controller, $url_prefix )
    {
    	$routes = array(
    			array(
    					'action' => 'EnablableItemChangeStateItemDisable',
    					'request' => 'GET',
    					'route' => '/disable/@id'
    			),
    			array(
    					'action' => 'EnablableItemChangeStateItemEnable',
    					'request' => 'GET',
    					'route' => '/enable/@id'
    			)
    	);    	 
    	// TODO: Add routes for bulk enable/disable action
    
    	// add all routes you can
    	$this->addBulkRoutes( $routes, $controller, $url_prefix );
    }
    
    /**
     * This method returns array of correctly formatted routes and operations assigned to them
     */
    public function getListRoutes()
    {
        $result = array();
        
        if (count( $this->routes ))
        {
            foreach ( $this->routes as $act )
            {
                
                $route_str = '';
                if (isset( $this->default_params['url_prefix'] ) && ! empty( $this->default_params['url_prefix'] ))
                {
                    $route_str = $this->default_params['url_prefix'];
                }
                
                if (is_array( $act['type'] ))
                {
                    $route_str = implode( '|', $act['type'] ) . ' ' . $route_str;
                }
                else
                {
                    $route_str = $act['type'] . ' ' . $route_str;
                }
                $route_str .= $act['route'];
                
                if (isset( $act['params']['ajax'] ) && (bool) ($act['params']['ajax']))
                {
                    $route_str .= ' [ajax]';
                }
                
                $action_str = '';
                if (isset( $act['params']['namespace'] ) && ! empty( $act['params']['namespace'] ))
                {
                    $action_str = (string) $act['params']['namespace'];
                }
                else
                {
                    if (isset( $this->default_params['namespace'] ) && ! empty( $this->default_params['namespace'] ))
                    {
                        $action_str = $this->default_params['namespace'];
                    }
                }
                $action_str .= '\\';
                
                if (isset( $act['params']['controller'] ))
                {
                    $action_str .= (string) $act['params']['controller'];
                }
                else
                {
                    if (isset( $this->default_params['controller'] ) && ! empty( $this->default_params['controller'] ))
                    {
                        $action_str .= $this->default_params['controller'];
                    }
                }
                $action_str .= '->' . (string) $act['params']['action'];
                
                $kbps = $this->default_params['kbps'];
                if( isset( $act['params']['kbps'] ) ){
                	$kbps = $act['params']['kbps'];
                }
                $ttl = $this->default_params['ttl'];
                if( isset( $act['params']['ttl'] ) ){
                	$ttl = $act['params']['ttl'];
                }
                
                $route = new \stdclass();
                $route->pattern = $route_str;
                $route->handler = $action_str;
                $route->ttl = $ttl;
                $route->kbps =  $kbps;
                $result[] = $route;
            }
        }
        
        return $result;
    }
}