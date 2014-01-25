<?php 
namespace Dsc;

abstract class Prefabs extends \Magic
{
    protected $document = array();
    protected $options = array();    
    protected $default_options = array();
    
    public function __construct($source=array(), $options=array())
    {
        $this->setOptions($options);
        
        if (!empty($source))
        {
            $this->bind($source, $this->options);
        }
    }
    
    public function setOptions( $options=array() )
    {
        $this->options = $options + $this->default_options + array(
            'append' => false,
            'ignored' => array()
        );
        
        if (!is_array($this->options['ignored']))
        {
            $this->options['ignored'] = \Base::instance()->split($this->options['ignored']);
        }
                
        return $this->options;
    }
    
    public function bind( $source, $options=array() )
    {
        $this->setOptions($options);
        
        if (!is_object($source) && !is_array($source))
        {
            throw new \Exception('Invalid source');
        }

        if (is_object($source))
        {
            $source = get_object_vars($source);
        }
        
        if ($this->options['append']) 
        {
            // add unknown keys to the object
            foreach ($source as $key=>$value)
            {
                if (!in_array($key, $this->options['ignored']))
                {
                    $this->$key = $value;
                }
            }
        } 
            else 
        {
            // ignore unknown keys
            foreach ($this->document as $key=>$value)
            {
                if (!in_array($key, $this->options['ignored']))
                {
                    if (isset($source[$key]))
                    {
                        $this->$key = $source[$key];
                    }                    
                }
            }            
        }
        
        return $this;
    }
    
    /**
     *	Return fields of object as an associative array
     *	@return array
     **/
    function cast() 
    {
        return $this->document;
    }
    
    /**
     *	Return TRUE if field is defined
     *	@return bool
     *	@param $key string
     **/
    function exists($key) {
        return \Dsc\ArrayHelper::exists( $this->document, $key );
    }
    
    /**
     *	Assign value to field
     *	@return scalar|FALSE
     *	@param $key string
     *	@param $val scalar
     **/
    function set($key,$val) {
        return \Dsc\ArrayHelper::set( $this->document, $key, $val );        
    }
    
    /**
     *	Retrieve value of field
     *	@return scalar|FALSE
     *	@param $key string
     **/
    function get($key, $default=null) {
        return \Dsc\ArrayHelper::get( $this->document, $key, $default );
    }
    
    /**
     *	Delete field
     *	@return NULL
     *	@param $key string
     **/
    function clear($key) {
        \Dsc\ArrayHelper::clear( $this->document, $key );
    }
}