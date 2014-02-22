<?php 
namespace Dsc;

class Singleton extends \Prefab
{
    protected $errors = array();

    public function __construct($config=array()){}
    
    /**
     * Add an error message.
     * 
     * @param string $error
     * @return \Dsc\Singleton
     */
    public function setError($error)
    {
        $error = trim( $error );
        if (!empty($error))
        {
            array_push($this->errors, $error);
        }
        
        return $this;
    }
    
    /**
     * Return all errors, if any.
     *
     * @return  array  Array of error messages.
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Resets all error messages
     */
    public function clearErrors()
    {
        $this->errors = array();
        return $this;
    }
    
    /**
     * Any errors set?  If so, check fails
     *
     */
    public function checkErrors()
    {
        $errors = $this->getErrors();
        if (empty($errors))
        {
            return $this;
        }
        
        $messages = implode(". ", $errors);
        
        throw new \Exception('Errors encountered - ' . $messages );
    }
}
?>