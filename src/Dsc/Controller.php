<?php 
namespace Dsc;

class Controller extends Singleton 
{
    use \Dsc\Traits\Meta;
    
    /**
     * URL for redirection.
     *
     * @var    string
     */
    protected $redirect;
    
    protected function outputJson($response)
    {
        $callback = $this->input->getAlnum('callback');
        $f3 = \Base::instance();
        
        header('Content-Type: application/json; '.
                'charset='.$f3->get('ENCODING'));
            
        if (!empty($callback)) {
            echo $callback . "(" . json_encode($response) . ")";
        } else {
            echo json_encode($response);
        }
    }
    
    protected  function outputCsv( $filename, $data, $csv_header = array() ){
    	header( 'Content-Type: text/csv');
    	header( 'Pragma: public' );
    	header( 'Case-Control: must-revalidate, post-check=0, pre-check=0' );
    	header( 'Case-Control: public' );
    	header( 'Content-Desription: File Transfer' );
    	header( 'Content-Disposition: attachment; filename='.$filename );
    	
    	if( !empty( $csv_header ) ){
    		foreach($csv_header as $h) {
    			$csv[] = '"' . str_replace('"', '""', $h) . '"';
    		}
    		echo implode(",", $csv) . "\r\n";    		
    	}
    	
    	foreach($data as $item) {
    		$csv = array();
    		foreach($item as $v) {
    			$csv[] = '"' . str_replace('"', '""', $v) . '"';
    		}
    		echo implode(",", $csv) . "\r\n";
    	}
    	return;    	
    }
    
    /**
     * Create a standard object for responding to ajax requests
     * 
     * @param array $data
     * @return \stdClass
     */
    protected function getJsonResponse( array $data=array() ) 
    {
        $response = new \stdClass();
        
        $response->code = 200;
        $response->message = null;
        $response->error = false;        
        $response->redirect = null;
        $response->result = null;
        
        foreach ($data as $key=>$value) 
        {
            $response->$key = $value;
        }
        
        return $response;
    }
    
    public function setRedirect( $url )
    {
        $this->redirect = $url;
    }
    
    public function getRedirect()
    {
        if (!empty($this->redirect)) {
            return $this->redirect;
        }
        
        return null;
    }
    
    /**
     * Requires an identity for the current user,
     * and either redirects to login screen (if global_app can be determined)
     * or throws an exception 
     *  
     * @throws \Exception
     * @return \Dsc\Controller
     */
    public function requireIdentity($message = 'Please login')
    {
        $f3 = \Base::instance();
        $identity = $this->getIdentity();
        if (empty($identity->id))
        {
            $path = $this->inputfilter->clean( $f3->hive()['PATH'], 'string' );
            if ($query = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY )) 
            {
            	$path .= '?' . $query; 
            }
            
            $global_app_name = strtolower( $f3->get('APP_NAME') );
            switch ($global_app_name) 
            {
            	case "admin":
            	    \Dsc\System::addMessage( $message );
            	    \Dsc\System::instance()->get('session')->set('admin.login.redirect', $path);
            	    $f3->reroute('/admin/login');            	    
            	    break;
            	case "site":
            	    \Dsc\System::addMessage( $message );
            	    \Dsc\System::instance()->get('session')->set('site.login.redirect', $path);
            	    $f3->reroute('/login');            	    
            	    break;
            	default:
                    throw new \Exception( 'Missing identity and unkown application' );
            	    break;
            }
            
            return false;
        }
        
        return $this;
    }
    
    /**
     * Checks if the user has access to the requested resource and method pair
     * 
     * @param unknown $resource
     * @param unknown $method
     * @param string $require_identity
     * @return boolean
     */
    public function checkAccess( $resource, $method, $require_identity=true )
    {
        $f3 = \Base::instance();
        $identity = $this->getIdentity();
        
        if ($require_identity) 
        {
            $this->requireIdentity();
        }
        
        // TODO If the user has multiple roles (is that possible) then loop through them        
        if ($hasAccess = \Dsc\System::instance()->get('acl')->isAllowed($identity->role, $resource, $method))
        {
            return $this;
        }
        
        if (\Base::instance()->get('DEBUG')) {
            \Dsc\System::addMessage( \Dsc\Debug::dump( 'Debugging is enabled := $role: ' . $identity->role . ", " . '$resource: ' . $resource . ", " . '$method: ' . $method) );
        }
        
        \Dsc\System::addMessage( 'You do not have access to perform that action.', 'error' );
        $global_app_name = strtolower( $f3->get('APP_NAME') );
        switch ($global_app_name)
        {
        	case "admin":
        	    \Base::instance()->reroute('/admin');
        	    break;
        	case "site":
        	    \Base::instance()->reroute('/');
        	    break;
        	default:
        	    throw new \Exception( 'No access and unkown application' );
        	    break;
        }
        
        return false;        
    }
    
    public function getIdentity()
    {
        // Make this reference an DI object
        $current_user = $this->auth->getIdentity();
        return $current_user;
    }
    
    /*
    public function afterRoute()
    {
        \FB::warn(round(memory_get_usage(TRUE)/1e3,1) . ' KB');
    }
    */
}
?>