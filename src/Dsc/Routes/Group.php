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
	
	function __construct(){
		$this->default_params = array(
				'controller' => '',
				'action' => '',
				'namespace' => '',
				'url_prefix' => ''
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
	 * @param $params
	 */
	public function setDefaults($params){
		$this->default_params = array_merge( $this->default_params, $params);
	}

	/**
	 * Adds a route to the group
	 * 
	 * @param $route 		String representation of the route
	 * @param $request_type Request type (POST/PUT/GET/DELETE)
	 * @param $params 		Parameters of the route (operation, only_AJAX)
	 * 
	 */
	public function add($route, $request_type, $params = array()){
		$orig_params = array(
				'ajax' => false,
				'namespace' => '',
				'controller' => '',
				'action' => ''
		);

		$params = array_merge($orig_params, $params );
		$this->routes []= array( 'route' => $route,
								 'type' => 	$request_type,
								 'params' =>$params);
	}

	/**
	 * Adds CRUD item routes for selected controller
	 *
	 * @param $controller  Name of controller
	 * @param $params 	   Parameters of the route (prefix_url is required)
	 *
	 */
	public function addCrudItem($controller, $params = array()){
		$orig_params = array(
				'prefix_url' => '',
				'exclude' => array()
		);
		$params = array_merge($orig_params, $params);
		if( strlen( $params['prefix_url'] ) == 0 ){ // use controller name as fallback option
			$params['prefix_url'] = '/'.strtolower( $controller );
		}
		
		// this array defines parameters for all CRUD operations for an item
		$operation_list = array(
			'add' => array(
						'action' => 'add',
						'request' => 'POST',
						'route' => '/add'
						),
			'create' => array(
							array(
									'action' => 'create',
									'request' => 'GET',
									'route' => '/create'
							),
							array(
									'action' => 'create',
									'request' => 'GET',
									'route' => ''
							)
						),
			'read' => array(
						'action' => 'read',
						'request' => 'GET',
						'route' => '/@id/read'
						),
			'edit' => array(
						'action' => 'edit',
						'request' => 'GET',
						'route' => '/@id/edit'
						),
			'update' => array(
						'action' => 'update',
						'request' => 'POST',
						'route' => '/@id/update'
						),
			'delete' => array(
						'action' => 'delete',
						'request' => array('GET', 'DELETE'),
						'route' => '/@id/delete'
						)
		);
		
		$available_operations = array_keys($operation_list);
		$operations = array_diff($available_operations, (array)$params['exclude']);
		$routes = array();
		foreach( $operations as $op ){
			$routes []= $operation_list[$op];
		}

		// add all routes you can
		if(count ($routes ) ){
			$this->addBulkRoutes( $routes, $controller, $params['prefix_url'] );
		}
	}
	
	/**
	 * Adds CRUD list routes for selected controller
	 *
	 * @param $controller  Name of controller
	 * @param $params 	   Parameters of the route (prefix_url is required)
	 *
	 */
	public function addCrudList($controller, $params = array()){
		$orig_params = array(
				'prefix_url' => '',
				'exclude' => array()
		);
		$params = array_merge($orig_params, $params);
		if( strlen( $params['prefix_url'] ) == 0 ){ // use controller name as fallback option
			$params['prefix_url'] = '/'.strtolower( $controller );
		}
	
		// this array defines parameters for all CRUD operations for an item
		$operation_list = array(
				'list' => array(
						array(
								'action' => 'index',
								'request' => array('GET', 'POST'),
								'route' => '/page/@page'
						),
						array(
								'action' => 'index',
								'request' => array('GET', 'POST'),
								'route' => ''
						)
				),
				'delete' => array(
						'action' => 'delete',
						'request' => array('GET', 'POST'),
						'route' => '/delete'
				)
		);
	
		$available_operations = array_keys($operation_list);
		$operations = array_diff($available_operations, (array)$params['exclude']);
		$routes = array();
		foreach( $operations as $op ){
			$routes []= $operation_list[$op];
		}

		// add all routes you can
		if(count ($routes ) ){
			$this->addBulkRoutes( $routes, $controller, $params['prefix_url'] );
		}
	}
	
	/**
	 * Adds routes in bulk
	 * 
	 * @param $routes_list List of routes to add
	 * @param $controller Controller for all added routes
	 * @param $prefix_url Prefix for all added routes
	 */
	private function addBulkRoutes($routes_list, $controller, $prefix_url){
		foreach( $routes_list as $routes ){
			// consider multiple routes for the same operation
			if( !isset($routes[0]) ){
				$routes = array( $routes );
			}
		
			foreach( $routes as $route ){
				$this->add($prefix_url.$route['route'],
						$route['request'],
						array(
								'controller' => $controller,
								'action' => $route['action']
						)
				);
		
			}
		}
	}
	
	/**
	 * This method returns array of correctly formatted routes and operations assigned to them
	 */
	public function getListRoutes(){
		$result = array();
		
		if( count( $this->routes) ){
			foreach($this->routes as $act){
				
				$route_str = '';
				if(isset($this->default_params['url_prefix']) && !empty($this->default_params['url_prefix'])) {
					$route_str = $this->default_params['url_prefix'];
				}
				
				if( is_array( $act['type']) ){
					$route_str = implode( '|', $act['type'] ).' '.$route_str;
				} else {
					$route_str = $act['type'].' '.$route_str;
				}				
				$route_str .= $act['route'];
				
				if( isset($act['params']['ajax'] ) && (bool)($act['params']['ajax'] )) {
					$route_str .= ' [ajax]';
				}
				
				
				$action_str = '';
				if( isset($act['params']['namespace']) && !empty($act['params']['namespace'])){
					$action_str = (string)$act['params']['namespace'];
				} else {
					if(isset($this->default_params['namespace']) && !empty($this->default_params['namespace'])) {
						$action_str = $this->default_params['namespace'];
					}
				}
				$action_str .= '\\';
				
				if( isset($act['params']['controller']) ){
					$action_str .= (string)$act['params']['controller'];
				} else {
					if(isset($this->default_params['controller']) && !empty($this->default_params['controller'])) {
						$action_str .= $this->default_params['controller'];
					}
				}
				$action_str .= '->'.(string)$act['params']['action'];
								
				$result []= array($route_str, $action_str);
			}
		}
		
		return $result;
	}
	
}