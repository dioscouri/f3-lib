<?php 
namespace Dsc;

/**
 * This is base bootstrap class for every f3 application enabling us to hook up some services before
 * the real bootstrapping occurs
 * 
 */
abstract class BaseBootstrap{

	protected $namespace = '';
	
	/**
	 * This needs to be set in bootstrap.php of every app
	 */
	protected $dir = '';
	
	/**
	 * Triggers a command for a specific part of application
	 * 
	 * @param $name Name of command
	 * @param $app Name of part of application
	 */
	public function command( $name, $app ){
		$app = ucwords($app);
		if( method_exists( $this, $name.$app ) ){
			$func = $name.$app;
			$this->$func();
		} else if( method_exists( $this, $name ) ){
			$this->$name( $app );
		}
	}
	
	protected function run($app){
		// handle other types of application, if no specific function defined
	}
	
	protected function runAdmin(){
		$listener = "\\".$this->namespace."\\Listener";
		if( class_exists( $listener ) ){
			// register event listener
			\Dsc\System::instance()->getDispatcher()->addListener($listener::instance());
		}
		$this->_runBase('Admin');
	}

	protected function runSite(){	
		$this->_runBase('Site');
	}
	
	/**
	 * This part is common for all running all parts of application (both Admin and Site)
	 * 
	 * @param $app Name of the part of application
	 */
	private function _runBase( $app ){
		$f3 = \Base::instance();
		$router = "\\".$this->namespace."\\Routes";
		if( !class_exists($router)){
			$router = "\\".$this->namespace."\\".$app."\\Routes";
			if( !class_exists($router)){
				$router = '';
			}
		}
		if( strlen( $router ) ) {
			// register all the routes
			\Dsc\System::instance()->get('router')->mount( new $router, $this->namespace );
		}

		// register the modules path, if you can
		$modules_path = $this->dir."/src/".$this->namespace."/Modules/";
		if( !file_exists( $modules_path ) ){
			// let's try more specific route
			$modules_path = $this->dir."/src/".$this->namespace.'/'.$app. "/Modules/";
			if( !file_exists( $modules_path ) ){ // not even here? maybe more luck next time
				$modules_path = '';
			}
		}
		
		if( strlen( $modules_path ) ){
			\Modules\Factory::registerPath( $modules_path );			
		}
		
		// append this app's UI folder to the path
		// new way
		
		if( file_exists( $this->dir . '/src/'.$this->namespace.'/'.$app.'/Views/' ) ){
			\Dsc\System::instance()->get('theme')->registerViewPath(
					$this->dir . '/src/'.$this->namespace.'/'.$app.'/Views/',
					$this->namespace.'/'.$app.'/Views'
			);
			// old way
			$ui = $f3->get('UI');
			$ui .= ";" .$this->dir . "/src/".$this->namespace.'/'.$app."/Views/";
			$f3->set('UI', $ui);
		} else {
			if( file_exists( $this->dir . '/src/'.$this->namespace.'/Views/' ) ){
				\Dsc\System::instance()->get('theme')->registerViewPath(
						$this->dir . '/src/'.$this->namespace.'/Views/',
						$this->namespace.'/Views'
				);
				// old way
				$ui = $f3->get('UI');
				$ui .= ";" .$this->dir . "/src/".$this->namespace."/Views/";
				$f3->set('UI', $ui);
					
			}
		}
		
	}
	
	
	protected function preRun($app){
		// handle other types of application, if no specific function defined
	}
	
	protected function preRunAdmin(){
	}

	protected function preRunSite(){
	}
	
	protected function postRun($app){
		// handle other types of application, if no specific function defined
	}
	
	protected function postRunAdmin(){
	}
	
	protected function postRunSite(){
	}
}
?>