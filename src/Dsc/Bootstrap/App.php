<?php
namespace Dsc\Bootstrap;

/**
 * This is base bootstrap class for every f3 application enabling us to hook up some services before
 * the real bootstrapping occurs
 */
abstract class App extends \Dsc\Bootstrap
{
	protected $minify_paths = array( "public/theme/", "public/" );
	protected $theme_path = "apps/Theme";
	
    protected function runSite()
    {
    	parent::runSite();
    	
    	$f3 = \Base::instance();

        // tell Minify where to find Media, CSS and JS files
        for( $i = 0; $i < count( $this->minify_paths ); $i++ ){
        	\Minify\Factory::registerPath($f3->get('PATH_ROOT') . $this->minify_paths[$i] );
        }
        
        // register the less css file
        $less_file = $f3->get('PATH_ROOT') .$this->theme_path  ."/Less/global.less.css";
        if( file_exists( $less_file ) ){
        	\Minify\Factory::registerLessCssSource( $less_file );
        }
        
        // add all media files
        $files = array();
        $files['css'] = $this->getCSS( 'Site' );
        $files['js'] = $this->getJS( 'Site' );
        $files['less'] = $this->getLESS('Site' );
        
        foreach( $files as $type=>$list ){
        	if( count( $list ) ){
        		foreach( $list as $file ) {
        			\Minify\Factory::{$type}($file);
        		}
        	}
        }
    }

    /**
     * This part is common for all running all parts of application (both Admin and Site)
     *
     * @param $app Name
     *            of the part of application
     */
    protected function _runBase( $app )
    {
    	parent::_runBase( $app );
    	$f3 = \Base::instance();
    	
    	// tell Minify where to find Media, CSS and JS files
    	for( $i = 0; $i < count( $this->minify_paths ); $i++ ){
    		\Minify\Factory::registerPath($f3->get('PATH_ROOT') . $this->minify_paths[$i] );
    	}
    	
    	// add all media files
    	$files = array();
    	$files['css'] = $this->getCSS( 'Site' );
    	$files['js'] = $this->getJS( 'Site' );
    	$files['less'] = $this->getLESS('Site' );
    	
    	foreach( $files as $type=>$list ){
    		if( count( $list ) ){
    			foreach( $list as $file ) {
    				\Minify\Factory::{$type}($file);
    			}
    		}
    	}
    }
    
    /**
     * This method takes care of registration all view files
     *
     * @param $app Name of the part of application
     */
    protected function registerViewFiles($app){
    	$f3 = \Base::instance();
    
    	// append this app's UI folder to the path
    	 
    	if (file_exists( $this->dir . '/' . $app . '/Views/' ))
    	{
    		\Dsc\System::instance()->get( 'theme' )->registerViewPath( $this->dir . '/' . $app . '/Views/', $this->namespace . '/' . $app . '/Views' );
    	}
    	if (file_exists( $this->dir . '/Views/' ))
    	{
    		\Dsc\System::instance()->get( 'theme' )->registerViewPath( $this->dir . '/Views/', $this->namespace . '/Views' );
    	}    		
    }
    
    /**
     * This method takesccase of registration all modules
     *
     * @param $app Name of the part of application
     */
    protected function registerModules($app){
    	// register the modules path, if you can
    	$modules_path = $this->dir . "/Modules/";
    	if (! file_exists( $modules_path ))
    	{
    		// let's try more specific route
    		$modules_path = $this->dir .  '/' . $app . "/Modules/";
    		if (! file_exists( $modules_path ))
    		{ // not even here? maybe more luck next time
    			$modules_path = '';
    		}
    	}
    	 
    	if (strlen( $modules_path ))
    	{
    		\Modules\Factory::registerPath( $modules_path );
    	}
    }

    /**
     * This method returns list of javascript files to be added to header
     * 
     * @param $app	Name of currently selected application (site or admin)
     */
	abstract protected function getJS($app);

	/**
	 * This method returns list of CSS files to be added to header
	 *
	 * @param $app	Name of currently selected application (site or admin)
	 */
	abstract protected function getCSS($app);

    /**
     * This method returns list of LESS files to be added to header
     * 
     * @param $app	Name of currently selected application (site or admin)
     */
	protected function getLESS($app) {
		return array();
	}
}