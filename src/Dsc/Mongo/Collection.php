<?php
namespace Dsc\Mongo;

/**
 * Collection class is used to represent and request items in a single Mongo collection
 *   
 * @author Rafael Diaz-Tushman
 *
 */
class Collection extends \Dsc\Magic
{
    public $_id; // MongoId
    
    protected $__doc = array();

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
    
    protected $__errors = array();
    
    protected $__last_operation = null;
    
    /**
     *	Instantiate class
     *	@return void
     *	@param $db object
     *	@param $collection string
     **/
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
    
    public function context()
    {
        if (empty($this->__config['context'])) {
            $this->__config['context'] = strtolower(get_class($this));
        }
    
        return $this->__config['context'];
    }
    
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
    
    public function getList($refresh=false) 
    {
    	return $this->getItems($refresh);
    }
    
    /**
     * An alias for find()
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
    
    public function fields()
    {
        $this->fetchFields();
    
        if (!empty($this->__query_params['fields'])) {
            return $this->__query_params['fields'];
        }
    
        return array();
    }
    
    protected function fetchFields()
    {
        $select_fields = $this->getState('select.fields');
        if (!empty($select_fields) && is_array($select_fields))
        {
            $this->__query_params['fields'] = $select_fields;
        }
    
        return $this;
    }
    
    public function conditions()
    {
        if (empty($this->__query_params['conditions'])) {
            $this->fetchConditions();
        }
    
        return $this->__query_params['conditions'];
    }
    
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
    
    public function collectionName()
    {
        // TODO Throw Exception if null?
        return $this->__collection_name;
    }
    
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
        
        if (\Dsc\ArrayHelper::exists( $this->__doc, $key ) || $this->isPublic($key)) {
        	return true;
        }
        return false;
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
        
        if (!property_exists($this,$key) || $this->isPublic($key)) {
        	\Dsc\ObjectHelper::set( $this, $key, $val );
        }
        
        return \Dsc\ArrayHelper::set( $this->__doc, $key, $val );
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
        
        if ($this->isPublic($key)) {
        	return $this->$key;
        } else {
            return \Dsc\ArrayHelper::get( $this->__doc, $key, $default );
        }
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
        
        $keys = explode('.', $key);
        $first_key = $keys[0];
        if ($this->isPublic($first_key)) 
        {
            \Dsc\ObjectHelper::clear( $this, $key );
        }
        
        \Dsc\ArrayHelper::clear( $this->__doc, $key );
    }
    
    /**
     * Returns an associative array of object's public properties
     * removing any that begin with a double-underscore (__) 
     *
     * @param   boolean  $public  If true, returns only the public properties.
     *
     * @return  array
     */
    public function cast($public = true)
    {
        $vars = get_object_vars($this);
        if ($public)
        {
            foreach ($vars as $key => $value)
            {
                if (substr($key, 0, 2) == '__' || !$this->isPublic($key))
                {
                    unset($vars[$key]);
                }
            }
        }
        return $vars;
    }
    
    protected function visible($key) 
    {
    	return $this->isPublic($key);
    }
    
    /**
     *	Return TRUE if property has public visibility
     *	@return bool
     *	@param $key string
     **/
    protected function isPublic($key) 
    {
        if (property_exists($this,$key)) {
            try {
                $ref=new \ReflectionProperty(get_class($this),$key);
                $out=$ref->ispublic();
                unset($ref);
            } catch (\Exception $e) {
            	// property is set but is not defined in the class: makes it a dynamic prop, so it's public
            	$out=true;
            }
            return $out;
        }
        return false;
    }
    
    public function log( $message, $priority='INFO', $category='General' )
    {
        \Dsc\Models\Logs::instance()->add( $message, $priority, $category );
    }
    
    public function bind( $source, $options=array() )
    {
        $this->setConfig($options);
        
        if (!is_object($source) && !is_array($source))
        {
            throw new \Exception('Invalid source');
        }
    
        if (is_object($source))
        {
            $source = get_object_vars($source);
        }
        
        if (empty($source)) 
        {
        	return $this;
        }
        
        $this->__doc = $source;
    
        if ($this->__config['append']) 
        {
            // add unknown keys to the object
            foreach ($source as $key=>$value)
            {
                if (!in_array($key, $this->__config['ignored']))
                {
                    $this->set($key, $value);
                }
            }
        } 
            else 
        {
            // ignore unknown keys
            foreach ($source as $key=>$value)
            {
                if (!in_array($key, $this->__config['ignored']) && $this->isPublic($key))
                {
                    $this->set($key, $value);
                }
            }            
        }
    
        return $this;
    }
    
    public function setConfig( $config=array() )
    {
        $this->__config = $config + $this->__config + $this->__default_config;
        
        if (!is_array($this->__config['ignored']))
        {
            $this->__config['ignored'] = \Base::instance()->split($this->__config['ignored']);
        }
        
        return $this;
    }
    
    public function load(array $conditions=array(), array $fields=array(), array $sort=array() )
    {
        if ($item = $this->setParam( 'conditions', $conditions )->setParam( 'fields', $fields )->setParam( 'sort', $sort )->getItem()) 
        {
        	$this->bind( $item );
        }
        
        return $this;
    }
    
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
    
    public function insert($document=array(), $options=array())
    {
        $this->__options = $options;
        
        $this->bind($document, $options);
        if (!empty($this->_id)) {
        	return $this->overwrite($document, $options);
        }
        
        // TODO add _pre and _post plugin events - Validate & Create
        $this->beforeValidate();
        $this->validate();
        $this->beforeSave();
        $this->beforeCreate();
        
        $this->set('_id', new \MongoId );
        if ($this->__last_operation = $this->collection()->insert( $this->cast() )) 
        {
        	$this->set('_id', $this->__doc['_id']);
        }
        
        $this->afterCreate();
        $this->afterSave();
        
        return $this;
    }
    
    public function update($document=array(), $options=array())
    {
        $this->__options = $options;
        
        if (!isset($options['overwrite']) || $options['overwrite']===true) {
        	return $this->overwrite($document, $options);
        }
        
        // TODO add _pre and _post plugin events - Update
        $this->beforeSave();
        $this->beforeUpdate();
        
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
    
    public function overwrite($document=array(), $options=array())
    {
        $this->__options = $options;
        
        $this->bind($document, $options);
        
        // TODO add _pre and _post plugin events - Validate & Update        
        $this->beforeValidate();
        $this->validate();
        $this->beforeSave();
        $this->beforeUpdate();
        
        $this->__last_operation = $this->collection()->update(
                array('_id'=> new \MongoId((string) $this->get('id') ) ),
                $this->cast(),
                array('upsert'=>false, 'multiple'=>false)
        );

        $this->afterUpdate();
        $this->afterSave();
        
        return $this;
    }
    
    public function remove()
    {
        // TODO add _pre and _post plugin events - Delete
        $this->beforeDelete();
        
        $this->__last_operation = $this->collection()->remove(
                array('_id'=> new \MongoId((string) $this->get('id') ) )
        );
        
        $this->afterDelete();
        
        return $this->lastOperation();
    }
    
    public function delete( $model=null )
    {
        if (!empty($model)) {
        	return $model->remove();
        }
        
        return $this->remove();
    }
    
    public function validate()
    {
        $errors = $this->getErrors();
        if (!empty($errors))
        {
            return false;
        }
                
        return $this;
    }
    
    public function validateWith( $validator )
    {
        if (!$validator->validate($this)) 
        {
        	$this->setError($validator->getError());
        }
        
        return $this;
    }
    
    /**
     * Add an error message.
     *
     * @param string $error
     * @return \Dsc\Singleton
     */
    public function setError($error)
    {
        if (is_string($error)) {
        	$error = new \Exception( $error );
        }
        
        if (is_a($error, 'Exception'))
        {
            array_push($this->__errors, $error);
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
        
        $messages = array();
        foreach ($errors as $exception) 
        {
            $messages[] = $exception->getMessage();        	
        }
        $messages = implode(". ", $messages);
    
        throw new \Exception( $messages );
    }
    
    public function lastOperation()
    {
        return $this->__last_operation;
    }
    
    protected function beforeValidate()
    {
        return $this->checkErrors();
    }
    
    protected function beforeSave()
    {
        return $this->checkErrors();
    }
    
    protected function beforeCreate()
    {
        return $this->checkErrors();
    }
    
    protected function beforeUpdate()
    {
        return $this->checkErrors();
    }
    
    protected function beforeDelete()
    {
        return $this->checkErrors();
    }

    protected function afterSave(){}
    
    protected function afterCreate(){}
    
    protected function afterUpdate(){}
    
    protected function afterDelete(){}    
}