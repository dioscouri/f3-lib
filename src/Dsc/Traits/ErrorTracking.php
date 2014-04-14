<?php
namespace Dsc\Traits;

trait ErrorTracking
{
    protected $__errors = array();
    
    /**
     * Add an error message.
     *
     * @param string $error            
     * @return \Dsc\Singleton
     */
    public function setError( $error )
    {
        if (is_string( $error ))
        {
            $error = new \Exception( $error );
        }
        
        if (is_a( $error, 'Exception' ))
        {
            array_push( $this->__errors, $error );
        }
        
        return $this;
    }

    /**
     * Return all errors, if any.
     *
     * @return array Array of error messages.
     */
    public function getErrors()
    {
        return $this->__errors;
    }

    /**
     * Resets all error messages
     */
    public function clearErrors()
    {
        $this->__errors = array();
        return $this;
    }

    /**
     * Any errors set? If so, check fails
     */
    public function checkErrors()
    {
        $errors = $this->getErrors();
        if (empty( $errors ))
        {
            return $this;
        }
        
        $messages = array();
        foreach ( $errors as $exception )
        {
            $messages[] = $exception->getMessage();
        }
        $messages = implode( ". ", $messages );
        
        throw new \Exception( $messages );
    }
}