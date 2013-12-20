<?php 
namespace Dsc;

class Model extends Object 
{
    protected $crud_item_key = "id";
    protected $state = null;
    protected $inputfilter = null;
    protected $context = null;
    protected $mapper = null;
    
    protected $fields = array();
    protected $filters = array();
    protected $options = array();
    
    /**
     * Valid filter/ordering fields.
     *
     * @var    array
     */
    protected $filter_fields = array();
    
    /**
     * Valid ordering direction values.
     *
     * @var    array
     */    
    protected $order_directions = array();
    protected $default_ordering_direction = null;
    protected $default_ordering_field = null;
    
    public function __construct($config=array())
    {
        parent::__construct($config);
        
        $state = empty($config['state']) ? new \Joomla\Registry\Registry : $config['state']; 
        $this->state = ($state instanceof \Joomla\Registry\Registry) ? $state : new \Joomla\Registry\Registry;
        
        $this->inputfilter = new \Joomla\Filter\InputFilter;
        
        $this->context = strtolower(get_class($this));
        
        if (isset($config['filter_fields']))
        {
            $this->filter_fields = $config['filter_fields'];
        }
        
        $this->order_directions = array('1', '-1', 'ASC', 'DESC', 'asc', 'desc');
        if (isset($config['order_directions']))
        {
            $this->order_directions = $config['order_directions'];
        }

        $this->mapper = $this->getMapper();
    }
    
    public function log( $message, $priority='INFO', $category='General' )
    {
        \Dsc\Models\Logs::instance()->add( $message, $priority, $category );
    }

    /**
     * Gets a property from the model's state, or the entire state if no property specified
     * @param $property
     * @param $default
     * @param string The variable type {@see JFilterInput::clean()}.
     *
     * @return unknown_type
     */
    public function getState( $property=null, $default=null, $return_type='default' )
    {
        $return = ($property === null) ? $this->state : $this->state->get($property, $default);

        return $this->inputfilter->clean( $return, $return_type );
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
            $this->state = $property;
        } else {
            $this->state->set($property, $value);
        }
        
        return $this;
    }
    
    public function emptyState()
    {
        $blank = new \Joomla\Registry\Registry;
        $this->setState( $blank );
        
        return $this;
    }


    /**
     * Method to set model filter variables, this is to allow you to direct effect filters without setting the state
     *
     * @param   string  $property  The name of the property.
     * @param   mixed   $value     The value of the property to set or null.
     *
     * @return  mixed  The previous value of the property or null if not set.
     */
    public function setFilter($filter, $value) {
        $this->filters[$filter] = $value;
    }

    public function getFilter($filter) {
       return isset($this->filters[$filter]) ? $this->filters[$filter] : null;
    }
    
    
    /**
     * Method to auto-populate the model state.
     *
     */
    public function populateState()
    {
        if ($filters = $this->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array'))
        {
            foreach ($filters as $name => $value)
            {
                $this->setState('filter.' . $name, $value);
            }
        }
        
        if ($list = $this->getUserStateFromRequest($this->context . '.list', 'list', array(), 'array'))
        {
            foreach ($list as $name => $value)
            {
                $this->setState('list.' . $name, $value);
            }
        }
        
        $offset = \Dsc\Pagination::findCurrentPage();        
        $this->setState('list.offset', $offset-1);

        if (is_null($this->getState('list.direction'))) 
        {
            $this->setState('list.direction', $this->default_ordering_direction);
        }
        
        if (is_null($this->getState('list.order'))) {
            $this->setState('list.order', $this->default_ordering_field);
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
        $input = $system->input;
        
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
     * Must always be set by the extending class if getList/getItem/paginate are used
     * @return mapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }
    
    /**
     * Returns the key name for the mapper item when crudding
     */
    public function getItemKey()
    {
        return $this->crud_item_key;
    }
    
    /**
     *
     * @return unknown
     */
    public function paginate()
    {
        $filters = $this->getFilters();
        $options = $this->getOptions();
        $pos = $this->getState('list.offset', 0, 'int');
        $size = $this->getState('list.limit', 10, 'int');
    
        $pagination = $this->getMapper()->paginate($pos, $size, $filters, $options);
    
        return $pagination;
    }
    
    public function getList( $refresh=false )
    {
        $fields = $this->getFields();
        $filters = $this->getFilters();
        $options = $this->getOptions();
    
        $mapper = $this->getMapper();
        if (!empty($fields) && method_exists($mapper, 'select')) 
        {
            if (is_a($mapper, '\DB\Mongo\Mapper')) {
                $items = $mapper->select($fields, $filters, $options);
            } else {
                $f3 = \Base::instance();
                $items = $mapper->select($f3->csv($fields), $filters, $options);
            }            
        }
        else 
        {
            $items = $mapper->find($filters, $options);
        }                
        
        /*
        if (is_a($mapper, '\DB\Mongo\Mapper')) {
            \Dsc\System::instance()->addMessage(\Dsc\Debug::dump($mapper->cursor()->explain()), 'warning');
        }
        */
    
        return $items;
    }
    
    public function getItem( $refresh=false )
    {
        $filters = $this->getFilters();
        $options = $this->getOptions();
    
        $mapper = $this->getMapper();
        $item = $mapper->findone($filters, $options);
            
        return $item;
    }
    
    public function getFields()
    {
        return $this->fetchFields();
    }
    
    protected function fetchFields()
    {
        $this->fields = array();

        $select_fields = $this->getState('select.fields');
        if (!empty($select_fields) && is_array($select_fields))
        {
            $this->fields = $select_fields;
        }
        
        return $this->fields;
    }
    
    public function getFilters()
    {
        return $this->fetchFilters();
    }
    
    protected function fetchFilters()
    {
        $this->filters = array();
        
        return $this->filters;
    }
    
    public function getOptions()
    {
        return $this->fetchOptions();
    }
    
    protected function fetchOptions()
    {
        $this->options = array();

        $this->options['order'] = $this->buildOrderClause();
    
        if ($this->getState('list.limit')) {
            $this->options['limit'] = (int) $this->getState('list.limit');
        }
    
        if (strlen($this->getState('list.offset'))) {
            $this->options['offset'] = (int) $this->getState('list.offset');
        }
    
        return $this->options;
    }
    
    protected function buildOrderClause()
    {
        $order = null;
        
        if ($this->getState('order_clause')) {
            return $this->getState('order_clause');             
        }

        if (is_null($this->getState('list.direction')))
        {
            $this->setState('list.direction', $this->default_ordering_direction);
        }
        
        if (is_null($this->getState('list.order'))) {
            $this->setState('list.order', $this->default_ordering_field);
        }
        
        if ($this->getState('list.order') && in_array($this->getState('list.order'), $this->filter_fields)) {
            
            $direction = 'ASC';
            if ($this->getState('list.direction') && in_array($this->getState('list.direction'), $this->order_directions)) {
                $direction = $this->getState('list.direction');
            }
            
            $order = $this->getState('list.order') . " " . $direction;            
        }
        
        return $order;
    }
    
    public function validate( $values, $options=array(), $mapper=null ) 
    {
        return $this->checkErrors();
    }
    
    public function save( $values, $options=array(), $mapper=null )
    {
        if (empty($options['skip_validation']))
        {
            $this->validate( $values, $options, $mapper );
        }
        
        $key = strtolower( get_class() ) . "." . microtime(true);
        $key = $this->inputfilter->clean($key, 'ALNUM');
        $f3 = \Base::instance();
        $f3->set($key, $values);
        
        // bind the mapper to the values array
        if (empty($mapper)) {
            $mapper = $this->getMapper();
        }
        $mapper->copyFrom( $key );
        $f3->clear($key);
       
        // do the save
        try {
            $mapper->save();
        } catch (\Exception $e) {
            $this->setError( $e->getMessage() );
            return $this->checkErrors();
        }
        
        return $mapper;
    }
    
    /**
     * An alias for the save command
     * 
     * @param unknown_type $values
     * @param unknown_type $options
     */
    public function create( $values, $options=array() ) 
    {
        return $this->save( $values, $options );
    }

    /**
     * An alias for the save command
     * 
     * @param unknown_type $mapper
     * @param unknown_type $values
     * @param unknown_type $options
     */
    public function update( $mapper, $values, $options=array() )
    {
        return $this->save( $values, $options, $mapper );
    }
    
    /**
     * Clone an item.  Data from $values takes precedence of data from cloned object.
     *
     * @param unknown_type $mapper
     * @param unknown_type $values
     * @param unknown_type $options
     */
    public function saveAs( $mapper, $values, $options=array() )
    {
        $item_data = $mapper->cast();
        $new_values = array_merge( $values, array_diff_key( $item_data, $values ) );
        
        return $this->save( $new_values, $options );
    }

    /**
     * Delete an item
     * 
     * @param unknown_type $mapper
     * @param unknown_type $options
     */
    public function delete( $mapper, $options=array() )
    {
        return $mapper->erase();
    }
}
?>
