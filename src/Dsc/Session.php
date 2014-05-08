<?php 
namespace Dsc;

class Session
{
    public function __construct($store)
    {
        $this->store = $store;
    }
    
    /**
     * Get the current session id
     */
    public function id()
    {
        return session_id();
    }
    
    /**
     * Get a session value, optionally from within the global_app's namespace
     * 
     * @param unknown $key
     * @param string $app_space
     */
    public function get( $key, $app_space=true )
    {
        if (empty($app_space))
        {
            return \Base::instance()->get('SESSION.' . $key );
        }
        else
        {
            $global_app_name = \Base::instance()->get('APP_NAME');
            return \Base::instance()->get('SESSION.' . $global_app_name . '.' . $key );
        }        
    }
    
    /**
     * Set a session value, optionally within the global_app's namespace
     * 
     * @param unknown $key
     * @param unknown $value
     * @param string $app_space
     */
    public function set( $key, $value, $app_space=true )
    {
        if (empty($app_space)) 
        {
            \Base::instance()->set('SESSION.' . $key , $value );
        }
        else 
        {
            $global_app_name = \Base::instance()->get('APP_NAME');
            \Base::instance()->set('SESSION.' . $global_app_name . '.' . $key , $value );
        }
    }
    
    /**
     * Empty a session value
     * 
     * @param unknown $key
     * @param string $app_space
     */
    public function remove( $key, $app_space=true )
    {
        $this->set( $key, null, $app_space );
    }
    
    /**
     * Completely destroy all session data
     * regardless of app namespace.
     * 
     * If you want to clear just an app's namespace, use
     * $this->remove( $app_name, false );
     * 
     * @return boolean
     */
    public function destroy()
    {
        \Base::instance()->clear('SESSION');
        session_unset();
        setcookie(session_name(),'',strtotime('-1 year'));
        unset($_COOKIE[session_name()]);
        header_remove('Set-Cookie');
        session_regenerate_id(true);
        session_start();
        return session_destroy();
    }
    
    /**
     * 
     * @param unknown $model_name
     * @param array $state
     */
    public function trackState( $model_name, array $state, $url_title=null, $url=null ) 
    {
        $key = 'trackState.' . $model_name;
        $current = $this->get( $key );
        if (empty($current) || !is_array($current)) {
        	$current = array();
        }        
        array_unshift( $current, (array) $state );
         
    	return $this->set( $key, $current );
    }
    
    /**
     * 
     * @param unknown $model_name
     * @return multitype:
     */
    public function lastState( $model_name ) 
    {
    	$state = array();
    	
    	$key = 'trackState.' . $model_name;
    	$current = $this->get( $key );
    	if (!empty($current) && is_array($current)) {
    	    $state = $current[0];
    	}

    	return $state;
    }
    
    /**
     *
     * @param unknown $model_name
     * @param array $state
     */
    public function trackUrl( $url_title, $url=null )
    {
    	if (empty($url)) {
    		$url = \Base::instance()->hive()['PATH'];
    	}
    	
    	$key = 'trackUrl';
    	$current = $this->get( $key );
    	if (empty($current) || !is_array($current)) {
    		$current = array();
    	}
    	array_unshift( $current, array(
    		'title' => $url_title,
    		'url' => $url
    	) );
    	 
    	return $this->set( $key, $current );
    }
    
    /**
     *
     * @param unknown $model_name
     * @return multitype:
     */
    public function lastUrl()
    {
    	$state = array();
    	 
    	$key = 'trackUrl';
    	$current = $this->get( $key );
    	if (!empty($current) && is_array($current)) {
    		$state = $current[0];
    	}
    
    	return $state;
    }
    
    /**
     *
     * @param unknown $model_name
     * @return multitype:
     */
    public function lastUrls()
    {
    	$state = array();
    
    	$key = 'trackUrl';
    	$current = (array) $this->get( $key );
    	if (!empty($current) && is_array($current)) {
    		$state = $current;
    	}
    
    	return $state;
    }
    
    /**
     *
     * @param unknown $model_name
     * @param array $state
     */
    public function clearUrls()
    {
    	$key = 'trackUrl';
    	
    	return $this->remove( $key );
    }
}
?>