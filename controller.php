<?php 
namespace Dsc;

class Controller extends Object 
{
    use \Dsc\Traits\Meta;
    
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
}
?>