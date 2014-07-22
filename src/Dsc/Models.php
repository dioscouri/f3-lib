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
    
    /**
     * Current state of the model, used when fetching
     */
    protected $__model_state = null;

    /**
     * Instantiate class, optionally binding it with an array/object
     * 
     * @param unknown $source
     * @param unknown $options
     */
    public function __construct( $source = array(), $options = array() )
    {
        $this->emptyState();
        
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
    public static function log( $message, $priority='INFO', $category='General' )
    {
        return \Dsc\Mongo\Collections\Logs::add( $message, $priority, $category );
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
        
        \Dsc\ArrayHelper::set( $this->__doc, $key, $val );
        
        return $this; 
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
    
    /**
     * Models are reused a lot, so their context must be 
     * url-specific but without the pagination parts of the url
     */
    public function context()
    {
        if (empty($this->__config['context'])) {
            $this->__config['context'] = strtolower(get_class($this));
        }
    
        $path = \Dsc\Pagination::checkRoute( \Base::instance()->hive()['PATH'] );
        $context = $path . '.' . $this->__config['context'];
    
        return $context;
    }
    
    /**
     * Gets the input filter object
     */
    public function inputFilter()
    {
        return \Dsc\System::instance()->get('inputfilter');
    }
    
    /**
     * Gets the output filter object
     */
    public function outputFilter()
    {
        return \Dsc\System::instance()->get('outputfilter');
    }
    
    /**
     * Method to auto-populate the model state.
     *
     */
    public function populateState()
    {
        if ($filters = $this->getUserStateFromRequest($this->context() . '.filter', 'filter', array(), 'array'))
        {
            $filters = \Dsc\ArrayHelper::dot( $filters );
            
            foreach ($filters as $name => $value)
            {
                $this->setState('filter.' . $name, $value);
            }
        }
    
        if ($list = $this->getUserStateFromRequest($this->context() . '.list', 'list', array(), 'array'))
        {
            $list = \Dsc\ArrayHelper::dot( $list );
            
            foreach ($list as $name => $value)
            {
                $this->setState('list.' . $name, $value);
            }
        }
    
        $offset = \Dsc\Pagination::findCurrentPage();
        $this->setState('list.offset', ($offset-1 >= 0) ? $offset-1 : 0);
    
        if (!is_null($this->getState('list.order')) && !is_null($this->getState('list.direction')))
        {
            switch(strtolower($this->getState('list.direction'))) {
                case "-1":
            	case "desc":
            	    $dir = -1;
            	    break;
            	case "1":
            	case "asc":
            	default:
            	    $dir = 1;
            	    break;
            }
    
            // TODO ensure that $this->getState('list.order') is a valid sorting field
            $this->setState('list.sort', array( $this->getState('list.order') => $dir ) );
        }
    
        if (is_null($this->getState('list.sort')))
        {
            $this->setState('list.sort', $this->__config['default_sort']);
            if (reset($this->__config['default_sort'])) {
                list($key, $value) = each($this->__config['default_sort']);
                $this->setState('list.order', $key);
                $this->setState('list.direction', $value);
            }
        }
    
        return $this;
    }
    
    /**
     * Gets the value of a user state variable and sets it in the session
     *
     * This is the same as the method in \Dsc\System except that this also can optionally
     * force you back to the first page when a filter has changed
     *
     * @param   string   $key        The key of the user state variable.
     * @param   string   $request    The name of the variable passed in a request.
     * @param   string   $default    The default value for the variable if not found. Optional.
     * @param   string   $type       Filter for the variable, for valid values see {@link \Joomla\Input\Input::clean()}. Optional.
     * @param   boolean  $resetPage  If true, the offset in request is set to zero
     *
     * @return  The request user state.
     */
    public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
    {
        $system = \Dsc\System::instance();
        $input = $system->get('input');
    
        $old_state = $system->getUserState($key);
        $cur_state = (!is_null($old_state)) ? $old_state : $default;
        $new_state = $input->get($request, null, $type);
    
        if (($cur_state != $new_state) && ($resetPage))
        {
            $input->set('list.offset', 0);
        }
    
        // Save the new value only if it is set in this request.
        if ($new_state !== null)
        {
            $system->setUserState($key, $new_state);
        }
        else
        {
            $new_state = $cur_state;
        }
    
        return $new_state;
    }
    
    /**
     * Gets a set state, cleaning it
     *
     * @param string $property
     * @param string $default
     * @param string $return_type
     */
    public function getState( $property=null, $default=null, $return_type='default' )
    {
        $return = ($property === null) ? $this->__model_state : $this->__model_state->get($property, $default);
    
        return $this->inputFilter()->clean( $return, $return_type );
    }
    
    /**
     * Method to set model state variables
     *
     * @param   string  $property  The name of the property.
     * @param   mixed   $value     The value of the property to set or null.
     *
     * @return  mixed  The previous value of the property or null if not set.
     */
    public function setState($property, $value = null)
    {
        if ($property instanceof \Joomla\Registry\Registry) {
            $this->__model_state = $property;
        } elseif (! $this->__model_state instanceof \Joomla\Registry\Registry) {
            $this->__model_state = new \Joomla\Registry\Registry;
            $this->__model_state->set($property, $value);
        } else {
            $this->__model_state->set($property, $value);
        }
    
        return $this;
    }
    
    /**
     * Empties the model's set state
     *
     * @return \Dsc\Mongo\Collection
     */
    public function emptyState()
    {
        $blank = new \Joomla\Registry\Registry;
        $this->setState( $blank );
    
        return $this;
    }
    
    /**
     * Returns the key name for the model
     */
    public function getItemKey()
    {
        return $this->__config['crud_item_key'];
    }
}