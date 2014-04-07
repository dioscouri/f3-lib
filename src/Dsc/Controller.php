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
    
    /**
     * Create a standard object for responding to ajax requests
     * 
     * @param array $data
     * @return \stdClass
     */
    protected function getJsonResponse( array $data=null ) 
    {
        $response = new \stdClass();
        
        $response->code = 200;
        $response->message = null;
        $response->error = false;        
        $response->redirect = null;
        $response->result = null;
        
        if (isset($data['code'])) {
            $response->code = $data['code'];
        }
        
        if (isset($data['message'])) {
            $response->message = $data['message'];
        }
        
        if (isset($data['error'])) {
            $response->error = $data['error'];
        }
        
        if (isset($data['redirect'])) {
            $response->redirect = $data['redirect'];
        }
        
        if (isset($data['result'])) {
            $response->result = $data['result'];
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
    
    public function checkAccess( $resource, $method, $require_identity=true )
    {
        $identity = $this->getIdentity();
        
        if ($require_identity && empty($identity->id)) 
        {
            \Dsc\System::addMessage( 'Please sign in.' );
            \Base::instance()->reroute('/admin/login');
            return false;
        }
        
        \Dsc\System::addMessage( \Dsc\Debug::dump( '\Dsc\Controller\isAllowed will check if the following identity has accessing to the resource & method below' ) );
        \Dsc\System::addMessage( \Dsc\Debug::dump( $identity ) );
        \Dsc\System::addMessage( \Dsc\Debug::dump('$resource: ' . $resource) );
        \Dsc\System::addMessage( \Dsc\Debug::dump('$method: ' . $method) );
        
        if ($hasAccess = \Dsc\System::instance()->get('acl')->isAllowed($identity->role, $resource, $method))
        {
            return true;
        }
        
        \Dsc\System::addMessage( 'You do not have access to perform that action.' );
        \Base::instance()->reroute('/admin');
        
        return false;        
    }
    
    public function getIdentity()
    {
        // Make this reference an DI object
        $current_user = $this->auth->getIdentity();
        return $current_user;
    }
}
?>