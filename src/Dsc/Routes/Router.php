<?php

namespace Dsc\Routes;

/**
 * Router class is used to manage all registered groups of routes for f3 apps
 * 
 * @author Lukas Polak
 */
class Router{

	private $groups = array();
	
	/**
	 * Mounts group of routes to the router
	 * 
	 * @param $group Object containing a group of routes
	 */
	public function mount($group){
		$this->groups []= $group;
	}
	
	/**
	 * Registers all routes from all mounted groups at once
	 */
	public function registerRoutes(){
		if( count( $this->groups ) ){
			$f3 = \Base::instance();
			foreach( $this->groups as $group ){
				$group->initialize();
				$routes = $group->getListRoutes();
				if( count( $routes ) ){
					foreach( $routes as $route ){
						$f3->route( $route[0], $route[1]);
					}
				}
			}
		}
	}
}