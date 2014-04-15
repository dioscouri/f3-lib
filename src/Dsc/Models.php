<?php
namespace Dsc;

class Models extends \Dsc\Magic
{
    use \Dsc\Traits\ErrorTracking;
    
    protected $__doc = array();
    
    /**
     * Default model configuration
     */
    protected $__default_config = array(
        'cache_enabled' => true,
        'cache_lifetime' => 0,
        'track_states' => true,
        'context' => null,
        'default_sort' => array(),
        'crud_item_key' => 'id',
        'append' => true,
        'ignored' => array() 
    );
    
    /**
     * Child classes should override this to customize their config
     */
    protected $__config = array();

    public function __construct( $source = array(), $options = array() )
    {
        $this->setConfig( $options );
        
        if (! empty( $source ))
        {
            $this->bind( $source, $this->__config );
        }
    }
    
    /**
     * Make a Log entry in the logger
     * 
     * @param unknown $message
     * @param string $priority
     * @param string $category
     */
    public function log( $message, $priority='INFO', $category='General' )
    {
        \Dsc\Models\Logs::instance()->add( $message, $priority, $category );
    }
    
    /**
     * Set the model's config options
     *
     * @param unknown $config            
     * @return \Dsc\Models
     */
    public function setConfig( $config = array() )
    {
        $this->__config = $config + $this->__config + $this->__default_config;
        
        if (! is_array( $this->__config['ignored'] ))
        {
            $this->__config['ignored'] = \Base::instance()->split( $this->__config['ignored'] );
        }
        
        return $this;
    }

    /**
     * Bind the object to a source array/object
     *
     * @param unknown $source            
     * @param unknown $options            
     * @throws \Exception
     * @return \Dsc\Models
     */
    public function bind( $source, $options = array() )
    {
        $this->setConfig( $options );
        
        if (! is_object( $source ) && ! is_array( $source ))
        {
            throw new \Exception( 'Invalid source' );
        }
        
        if (is_object( $source ))
        {
            $source = get_object_vars( $source );
        }
        
        if (empty( $source ))
        {
            return $this;
        }
        
        $this->__doc = $source;
        
        if ($this->__config['append'])
        {
            // add unknown keys to the object
            foreach ( $source as $key => $value )
            {
                if (! in_array( $key, $this->__config['ignored'] ))
                {
                    $this->set( $key, $value );
                }
            }
        }
        else
        {
            // ignore unknown keys
            foreach ( $source as $key => $value )
            {
                if (! in_array( $key, $this->__config['ignored'] ) && $this->exists( $key ))
                {
                    $this->set( $key, $value );
                }
            }
        }
        
        return $this;
    }

    /**
     * Returns an associative array of object's public properties
     * removing any that begin with a double-underscore (__)
     *
     * @param boolean $public
     *            If true, returns only the public properties.
     *            
     * @return array
     */
    public function cast( $public = true )
    {
        $vars = get_object_vars( $this );
        if ($public)
        {
            foreach ( $vars as $key => $value )
            {
                if (substr( $key, 0, 2 ) == '__' || ! $this->isPublic( $key ))
                {
                    unset( $vars[$key] );
                }
            }
        }
        return $vars;
    }

    /**
     * Return TRUE if field is defined
     *
     * @return bool
     * @param $key string            
     *
     */
    function exists( $key )
    {
        if (\Dsc\ArrayHelper::exists( $this->__doc, $key ) || $this->isPublic( $key ))
        {
            return true;
        }
        return false;
    }

    /**
     * Assign value to field
     *
     * @return scalar FALSE
     * @param $key string            
     * @param $val scalar            
     *
     */
    function set( $key, $val )
    {
        if (! property_exists( $this, $key ) || $this->isPublic( $key ))
        {
            \Dsc\ObjectHelper::set( $this, $key, $val );
        }
        
        return \Dsc\ArrayHelper::set( $this->__doc, $key, $val );
    }

    /**
     * Retrieve value of field
     *
     * @return scalar FALSE
     * @param $key string            
     *
     */
    function get( $key, $default = null )
    {
        if ($this->isPublic( $key ))
        {
            return $this->$key;
        }
        else
        {
            return \Dsc\ArrayHelper::get( $this->cast(), $key, $default );
        }
    }

    /**
     * Delete field
     *
     * @return NULL
     * @param $key string            
     *
     */
    function clear( $key )
    {
        $keys = explode( '.', $key );
        $first_key = $keys[0];
        if ($this->isPublic( $first_key ))
        {
            \Dsc\ObjectHelper::clear( $this, $key );
        }
        
        \Dsc\ArrayHelper::clear( $this->__doc, $key );
        
        return $this;
    }

    /**
     * Overrides method in \Dsc\Magic and uses custom isPublic method instead
     *
     * @param unknown $key            
     */
    protected function visible( $key )
    {
        return $this->isPublic( $key );
    }

    /**
     * Return TRUE if property has public visibility
     *
     * @return bool
     * @param $key string            
     *
     */
    protected function isPublic( $key )
    {
        if (property_exists( $this, $key ))
        {
            try
            {
                $ref = new \ReflectionProperty( get_class( $this ), $key );
                $out = $ref->ispublic();
                unset( $ref );
            }
            catch ( \Exception $e )
            {
                // property is set but is not defined in the class: makes it a dynamic prop, so it's public
                $out = true;
            }
            return $out;
        }
        return false;
    }
}