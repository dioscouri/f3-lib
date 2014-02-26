<?php

namespace Dsc\Routes;

/**
 * Group class is used to keep track of a group of routes with similar aspects (the same controller, the same f3-app and etc)
 * 
 * @author Lukas Polak
 */
class Group{

	private $default_params;
	private $routes = array();
	
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
	 * NOTE: This method should be overriden by every group
	 */
	public function initialize(){
	}
	
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