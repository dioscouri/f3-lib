<?php
namespace Dsc\Mongo;

/**
 * Collection class is used to represent and request items in a single Mongo collection
 *   
 * @author Rafael Diaz-Tushman
 *
 */
class Collection extends \Dsc\Models
{
    public $_id; // MongoId

    protected $__collection_name = null;
        
    protected $__default_config = array(
        'cache_enabled' => true,
        'cache_lifetime' => 0,
        'track_states' => true,
        'context' => null,
        'default_sort' => array(
            '_id' => 1 
        ),
        'crud_item_key' => '_id',
        'append' => true,
        'ignored' => array()
    );
    
    /**
     * Child classes should override this to customize their config
     * 
     * @var unknown
     */
    protected $__config = array();
    
    protected $__query_params = array(
        'conditions' => array(),
        'fields' => array(),
        'sort' => array(),
        'limit' => null,
        'skip' => 0 
    );
    
    protected $__model_state = null;
    
    /**
     * Desired options during CRUD actions
     * 
     * @var unknown
     */
    protected $__options = array();
    
    protected $__last_operation = null;

    /**
     * Instantiate class, optionally binding it with an array/object
     * 
     * @param string $data
     * @param unknown $options
     */
    public function __construct($data=null, $options=array()) 
    {
        $this->emptyState();
        $this->setConfig($options);
        
        if (!empty($data)) {
        	$this->bind($data, $options);
        }
    }
    
    /**
     * Manually set a query param without using setState()
     *
     */
    public function setParam( $param, $value )
    {
        if (array_key_exists($param, $this->__query_params))
        {
            $this->__query_params[$param] = $value;
        }
    
        return $this;
    }
    
    /**
     * Set a condition in the query
     *
     */
    public function setCondition( $key, $value )
    {
        $this->__query_params['conditions'][$key] = $value;
    
        return $this;
    }
    
    /**
     * Get a set condition in the query
     *
     */
    public function getCondition( $key )
    {
        if (isset($this->__query_params['conditions'][$key])) 
        {
            return $this->__query_params['conditions'][$key];
        }
    
        return null;
    }
    
    /**
     * Get a parameter from the query
     * or return the entire query_params array
     *
     * @param unknown $param
     * @return NULL
     */
    public function getParam( $param=null )
    {
        if ($param === null)
        {
            return $this->__query_params;
        }
    
        if (array_key_exists($param, $this->__query_params))
        {
            return $this->__query_params[$param];
        }
    
        return null;
    }
    
    /**
     * 
     */
    public function context()
    {
        if (empty($this->__config['context'])) {
            $this->__config['context'] = strtolower(get_class($this));
        }
    
        return $this->__config['context'];
    }
    
    /**
     * Gets the input filter object
     */
    public function inputFilter()
    {
        return \Dsc\System::instance()->get('inputfilter');
    }
    
    /**
     * Method to auto-populate the model state.
     *
     */
    public function populateState()
    {
        if ($filters = $this->getUserStateFromRequest($this->context() . '.filter', 'filter', array(), 'array'))
        {
            foreach ($filters as $name => $value)
            {
                $this->setState('filter.' . $name, $value);
            }
        }
    
        if ($list = $this->getUserStateFromRequest($this->context() . '.list', 'list', array(), 'array'))
        {
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
                case "desc":
                    $dir = -1;
                    break;                
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
    
    /**
     * An alias for getItems()
     * 
     * @param string $refresh
     */
    public function getList($refresh=false) 
    {
    	return $this->getItems($refresh);
    }
    
    /**
     * Gets items from a collection with a query
     * that uses the model's state
     * and implements caching (if enabled)
     */
    public function getItems($refresh=false)
    {
        if (is_null($this->getState('list.sort')))
        {
            $this->setState('list.sort', $this->__config['default_sort']);
        }
        $this->setParam('sort', $this->getState('list.sort'));
        
        // TODO Store the state
        // TODO Implement caching
        return $this->fetchItems();
    }
    
    /**
     * Fetches multiple items from a collection using set conditions
     * 
     * @return multitype:\Dsc\Mongo\Collection
     */
    protected function fetchItems()
    {
        $this->__cursor = $this->collection()->find($this->conditions(), $this->fields());

        if ($this->getParam('sort')) {
            $this->__cursor->sort($this->getParam('sort'));
        }
        if ($this->getParam('limit')) {
            $this->__cursor->limit($this->getParam('limit'));
        }
        if ($this->getParam('skip')) {
            $this->__cursor->skip($this->getParam('skip'));
        }
        
        $items = array();
        foreach ($this->__cursor as $doc) {
        	$item = new static( $doc );
        	$items[] = $item;
        }           
    
        return $items;
    }
    
    /**
     * An alias for findOne
     * that uses the model's state
     * and implements caching (if enabled)
     */
    public function getItem($refresh=false)
    {
        // TODO Store the state
        // TODO Implement caching
        return $this->fetchItem();
    }
    
    /**
     * Fetches an item from the collection using set conditions
     * 
     * @return Ambigous <NULL, \Dsc\Mongo\Collection>
     */
    protected function fetchItem()
    {
        $this->__cursor = $this->collection()->find($this->conditions(), $this->fields());
        
        if ($this->getParam('sort')) {
            $this->__cursor->sort($this->getParam('sort'));
        }
        $this->__cursor->limit(1);
        $this->__cursor->skip(0);
        
        $item = null;
        if ($this->__cursor->hasNext()) {
            $item = new static( $this->__cursor->getNext() );
        }
        
        return $item;
    }
    
    /**
     * Returns a pagination object
     * merged with a result set
     *
     * @return \Dsc\Pagination
     */
    public function paginate($refresh=false)
    {
        $size = $this->getState('list.limit', 30, 'int');
        $this->setParam('limit', $size);
        $this->setParam('skip', $this->getState('list.offset', 0, 'int') * $size);
        
        $total = $this->collection()->count( $this->conditions() );
        $result = new \Dsc\Pagination( $total, $size );
        $result->items = $this->getItems($refresh);
    
        return $result;
    }
    
    /**
     * Gets the array of fields set to be returned by the next query,
     * fetching them if necessary
     *  
     * @return array
     */
    public function fields()
    {
        if (empty($this->__query_params['fields'])) {
            $this->fetchFields();
        }
    
        return $this->__query_params['fields'];
    }
    
    /**
     * Fetches the array of fields to be returned by the next query
     * 
     * @return \Dsc\Mongo\Collection
     */
    protected function fetchFields()
    {
        $select_fields = $this->getState('select.fields');
        if (!empty($select_fields) && is_array($select_fields))
        {
            $this->__query_params['fields'] = $select_fields;
        }
    
        return $this;
    }
    
    /**
     * Gets the array of conditions set for the next query,
     * fetching them if necessary
     * 
     * @return array
     */
    public function conditions()
    {
        if (empty($this->__query_params['conditions'])) {
            $this->fetchConditions();
        }
    
        return $this->__query_params['conditions'];
    }
    
    /**
     * Fetches the conditions for the next query
     * 
     * @return \Dsc\Mongo\Collection
     */
    protected function fetchConditions()
    {
        $this->__query_params['conditions'] = array();
        
        $filter_id = $this->getState('filter.id');
        if (strlen($filter_id))
        {
            $this->setCondition('_id', new \MongoId((string) $filter_id));
        }
        
        $filter_ids = $this->getState('filter.ids');
        if (!empty($filter_ids) && is_array($filter_ids))
        {
            $_ids = array();
            foreach ($filter_ids as $_filter_id) 
            {
            	$_ids[] = new \MongoId( (string) $_filter_id);
            }
            $this->setCondition('_id', array('$in' => $_ids) );
        }
        
        return $this;
    }
    
    /**
     * Gets the global Mongo connection 
     */
    public function getDb()
    {
        return \Dsc\System::instance()->get('mongo');
    }
    
    /**
     * This is static so you can do 
     * YourModel::collection()->find() or anything else with the MongoCollection object
     */
    public static function collection()
    {
        if (empty($this)) {
            $item = new static();
            return $item->getDb()->selectCollection( $item->collectionName() );
        }

        return $this->getDb()->selectCollection( $this->collectionName() );
    }
    
    /**
     * Gets the collection name for this model
     */
    public function collectionName()
    {
        // Throw Exception if null
        if (empty($this->__collection_name)) 
        {
        	throw new \Exception('Must specify a collection name');
        }
        
        return $this->__collection_name;
    }
    
    /**
     * Finds items in the collection based on set conditions
     * 
     * @param unknown $conditions
     * @param unknown $fields
     */
    public static function find( $conditions=array(), $fields=array() )
    {
        if (empty($this)) {
            $model = new static();
        } else {
            $model = clone $this;
        }

        $sort = $model->__config['default_sort'];
        if (isset($conditions['sort'])) {
        	$sort = $conditions['sort'];
        	unset($conditions['sort']);
        }
        $model->setParam('sort', $sort);
        
        if (isset($conditions['limit'])) {
            $limit = $conditions['limit'];
            unset($conditions['limit']);
            $model->setParam('limit', $limit);
        }
        
        if (isset($conditions['skip'])) {
            $skip = $conditions['skip'];
            unset($conditions['skip']);
            $model->setParam('skip', $skip);
        }
        
        $model->setParam('conditions', $conditions);
        $model->setParam('fields', $fields);
        
        return $model->getItems();
    }
    
    /**
     *	Return TRUE if field is defined
     *	@return bool
     *	@param $key string
     **/
    function exists($key) {
        if ($key == 'id') {
            $key = '_id';
        }
        
        return parent::exists($key);
    }
    
    /**
     *	Assign value to field
     *	@return scalar|FALSE
     *	@param $key string
     *	@param $val scalar
     **/
    function set($key,$val) 
    {
        if ($key == 'id') {
            $key = '_id';
        }
        
        return parent::set($key, $val);
    }
    
    /**
     *	Retrieve value of field
     *	@return scalar|FALSE
     *	@param $key string
     **/
    function get($key, $default=null) 
    {
        if ($key == 'id') {
            $key = '_id';
        }

        return parent::get($key, $default);
    }
    
    /**
     *	Delete field
     *	@return NULL
     *	@param $key string
     **/
    function clear($key) {
        if ($key == 'id') {
            $key = '_id';
        }
        
        return parent::clear($key);
    }

    /**
     * Load a single Item from the collection and bind it to $this
     *  
     * @param array $conditions
     * @param array $fields
     * @param array $sort
     * @return \Dsc\Mongo\Collection
     */
    public function load(array $conditions=array(), array $fields=array(), array $sort=array() )
    {
        if ($item = $this->setParam( 'conditions', $conditions )->setParam( 'fields', $fields )->setParam( 'sort', $sort )->getItem()) 
        {
        	$this->bind( $item );
        }
        
        return $this;
    }
    
    /**
     * Save an item
     * 
     * @param unknown $document
     * @param unknown $options
     */
    public function save($document=array(), $options=array())
    {
        $this->__options = $options;
        
        if (!empty($this->_id)) {
        	return $this->overwrite($document, $options);
        }
        return $this->insert($document, $options);
    }
    
    /**
     * Clone an item.  Data from $values takes precedence of data from cloned object.
     *
     * @param unknown_type $mapper
     * @param unknown_type $values
     * @param unknown_type $options
     */
    public function saveAs( $document=array(), $options=array() )
    {
        $item_data = $this->cast();
        $new_values = array_merge( $document, array_diff_key( $item_data, $document ) );
        unset($new_values[$this->getItemKey()]);
        $item = new static( $new_values );
            
        return $item->insert(array(), $options);
    }
    
    /**
     * An Alias for insert()
     * 
     * @param unknown $document
     * @param unknown $options
     */
    public function create($document=array(), $options=array())
    {
        return $this->insert( $document, $options );
    }
    
    /**
     * 
     * @param unknown $document
     * @param unknown $options
     * @return \Dsc\Mongo\Collection
     */
    public function insert($document=array(), $options=array())
    {
        $this->__options = $options;
        
        $this->bind($document, $options);
        
        $this->beforeValidate();
        $this->validate();
        $this->beforeCreate();
        $this->beforeSave();
        
        if (!$this->get('id')) {
            $this->set('_id', new \MongoId );
        }
                
        if ($this->__last_operation = $this->collection()->insert( $this->cast() )) 
        {
        	$this->set('_id', $this->__doc['_id']);
        }
        
        $this->afterCreate();
        $this->afterSave();
        
        return $this;
    }
    
    /**
     * 
     * @param unknown $document
     * @param unknown $options
     */
    public function update($document=array(), $options=array())
    {
        $this->__options = $options;
        
        if (!isset($options['overwrite']) || $options['overwrite']===true) {
        	return $this->overwrite($document, $options);
        }
        
        $this->beforeUpdate();
        $this->beforeSave();
        
        // otherwise do a selective update with $set = array() and multi=false
        $this->__last_operation = $this->collection()->update(
                array('_id'=> new \MongoId((string) $this->get('id') ) ),
                array('$set' => $document ),
                array('multiple'=>false)
        );
                
        $this->afterUpdate();
        $this->afterSave();
                
        return $this->lastOperation();
    }
    
    /**
     * 
     * @param unknown $document
     * @param unknown $options
     * @return \Dsc\Mongo\Collection
     */
    public function overwrite($document=array(), $options=array())
    {
        $this->__options = $options;
        $this->bind($document, $options);
     
        $this->beforeValidate();
        $this->validate();
        $this->beforeUpdate();
        $this->beforeSave();
        
        $this->__last_operation = $this->collection()->update(
                array('_id'=> new \MongoId((string) $this->get('id') ) ),
                $this->cast(),
                array('upsert'=>true, 'multiple'=>false)
        );

        $this->afterUpdate();
        $this->afterSave();
        
        return $this;
    }
    
    /**
     * 
     */
    public function remove()
    {
        $this->beforeDelete();
        
        $this->__last_operation = $this->collection()->remove(
                array('_id'=> new \MongoId((string) $this->get('id') ) )
        );
        
        $this->afterDelete();
        
        return $this->lastOperation();
    }
    
    /**
     * 
     * @param string $model
     */
    public function delete( $model=null )
    {
        if (!empty($model)) {
        	return $model->remove();
        }
        
        return $this->remove();
    }
    
    /**
     * 
     * @return boolean|\Dsc\Mongo\Collection
     */
    public function validate()
    {
        $errors = $this->getErrors();
        if (!empty($errors))
        {
            return false;
        }
                
        return $this;
    }
    
    /**
     * 
     * @param unknown $validator
     * @return \Dsc\Mongo\Collection
     */
    public function validateWith( $validator )
    {
        if (!$validator->validate($this)) 
        {
        	$this->setError($validator->getError());
        }
        
        return $this;
    }

    /**
     * Gets the last operation result
     * 
     */
    public function lastOperation()
    {
        return $this->__last_operation;
    }
    
    protected function beforeValidate()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'beforeValidate' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        if ($event->isStopped()) {
            $this->setError( $event->getArgument('error') );
        }
                
        return $this->checkErrors();
    }
    
    protected function beforeSave()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'beforeSave' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        if ($event->isStopped()) {
            $this->setError( $event->getArgument('error') );
        }
                
        return $this->checkErrors();
    }
    
    protected function beforeCreate()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'beforeCreate' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        if ($event->isStopped()) {
            $this->setError( $event->getArgument('error') );
        }
                
        return $this->checkErrors();
    }
    
    protected function beforeUpdate()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'beforeUpdate' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        if ($event->isStopped()) {
            $this->setError( $event->getArgument('error') );
        }
        
        return $this->checkErrors();
    }
    
    protected function beforeDelete()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'beforeDelete' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        if ($event->isStopped()) {
            $this->setError( $event->getArgument('error') );
        }
        
        return $this->checkErrors();
    }

    protected function afterSave()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'afterSave' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
    }
    
    protected function afterCreate()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'afterCreate' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);    	
    }
    
    protected function afterUpdate()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'afterUpdate' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);    	
    }
    
    protected function afterDelete()
    {
        $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
        $event = (new \Joomla\Event\Event( 'afterDelete' . $eventNameSuffix ))->addArgument('model', $this);
        $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);    	
    }    
}