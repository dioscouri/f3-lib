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
    
    public function __construct($config=array())
    {
        parent::__construct($config);
        
        $this->input = new \Joomla\Input\Input;
        $this->inputfilter = new \Joomla\Filter\InputFilter;
    }
    
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
    
    protected function getJsonResponse( $data ) 
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
    
    public function isAllowed( $identity, $resource, $method )
    {
        \Dsc\System::addMessage( \Dsc\Debug::dump( '\Dsc\Controller\isAllowed will check if the following identity has accessing to the resource & method below' ) );
        \Dsc\System::addMessage( \Dsc\Debug::dump( $identity ) );
        \Dsc\System::addMessage( \Dsc\Debug::dump('$resource: ' . $resource) );
        \Dsc\System::addMessage( \Dsc\Debug::dump('$method: ' . $method) );
    }
    
    public function getIdentity()
    {
        // TODO Make this reference an DI object
        $current_user = new \Users\Models\Users;
        $old_user = \Base::instance()->get('SESSION.admin.user');
        $current_user->bind($old_user);
        
        return $current_user;
    }
}
?>