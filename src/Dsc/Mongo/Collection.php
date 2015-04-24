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
        'cache_lifetime' => 900,
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
    
    /**
     * Desired options during CRUD actions
     * 
     * @var unknown
     */
    protected $__options = array();
    
    protected $__last_operation = null;
    
    public function defaultSort()
    {
        return $this->__config['default_sort'];
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
     * Manually set a query params
     *
     */
    public function setParams( $params )
    {
        $this->__query_params = $params;
    
        return $this;
    }
    
    /**
     * Set a condition in the query
     *
     */
    public function setCondition( $key, $value, $method='overwrite' )
    {
        switch ($method) 
        {
        	case "merge":
        	    if (empty($this->__query_params['conditions'][$key]) || !is_array($this->__query_params['conditions'][$key])) {
        	        $this->__query_params['conditions'][$key] = array();
        	    }
        	    $this->__query_params['conditions'][$key] = array_merge($this->__query_params['conditions'][$key], $value);
        	    break;
        	case "append":
        	    if (empty($this->__query_params['conditions'][$key]) || !is_array($this->__query_params['conditions'][$key])) {
        	        $this->__query_params['conditions'][$key] = array();
        	    }
        	    $this->__query_params['conditions'][$key][] = $value;
        	    break;
        	case "prepend":
        	    if (empty($this->__query_params['conditions'][$key]) || !is_array($this->__query_params['conditions'][$key])) {
        	        $this->__query_params['conditions'][$key] = array();
        	    }
        	    array_unshift( $this->__query_params['conditions'][$key], $value );
        	    break;
        	case "overwrite":
        	default:
        	    $this->__query_params['conditions'][$key] = $value;
        	    break;
        }
        
    
        return $this;
    }

    /**
     *
     * @param unknown $key
     */
    public function unsetCondition( $key )
    {
        unset( $this->__query_params['conditions'][$key] );
    
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
            if (!empty($this->getParam('sort'))) {
                $this->setState('list.sort', $this->getParam('sort'));
            } else {
                $this->setState('list.sort', $this->__config['default_sort']);
            }
        }
        $this->setParam('sort', $this->getState('list.sort'));
        
        if ($this->getState('list.limit'))
        {
            $this->setParam('limit', $this->getState('list.limit'));
        }
        
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
     * Fetches multiple random items from a collection using set conditions
     * 
     * @param $forceUnique 		All items have to be unique
     */
    public function getItemsRandom($forceUnique = true)
    {
        if (is_null($this->getState('list.sort')))
        {
            if (!empty($this->getParam('sort'))) {
                $this->setState('list.sort', $this->getParam('sort'));
            } else {
                $this->setState('list.sort', $this->__config['default_sort']);
            }
        }
        $this->setParam('sort', $this->getState('list.sort'));
        
        if ($this->getState('list.limit'))
        {
            $this->setParam('limit', $this->getState('list.limit'));
        }
                
    	$conditions = $this->conditions();
    	$count = $this->collection()->find( $conditions )->count();
    	
    	$limit = $this->getParam('limit');
    	if( empty( $limit ) ){ // precaution in case limit is not set
    		$limit = 1;
    	}
    	
    	if( $forceUnique && $limit > $count ){ // prevent infinite loop by looking for more  random documents than the collection really contains
    		$limit = $count;
    	}
    	
    	$items = array();
    	for( $i = 0; $i < $limit; $i++ ){
    		$not_unique = true;
    		$item = null;
    		while( $not_unique || !$forceUnique ){
    			$rand = rand(0, $count-1);
    			$item = new static( 
    					$this->collection()->find( $conditions, $this->fields())
    										->limit(-1)
    										->skip($rand)
    											->getNext()
					    			);
    			// check uniqueness by comparing IDs
    			$not_unique = false; // presume it's unique
    			foreach( (array) $items as $it ){
    				if( (string)$it->id == (string)$item->id ) { // we found match so a new document needs to be fetched
    					$not_unique = true;
    					break;
    				}
    			}
    		}
    		
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
        return $this->fetchItem($refresh);
    }
    
  /**
     * 
     * @return \Dsc\Mongo\Collection
     */
    protected function fetchItem($refresh=false)
    {
      
        if ($this->getParam('sort')) {
        	$this->__cursor = $this->collection()->find($this->conditions($refresh), $this->fields($refresh));
            $this->__cursor->sort($this->getParam('sort'));
            $this->__cursor->limit(1);
            $this->__cursor->skip(0);
            $doc = $this->__cursor->next();
        } else {
        	$doc = $this->collection()->findOne($this->conditions($refresh), $this->fields($refresh));
        }
        
        $item = null;
        if ($doc) 
        {
            $item = new static( $doc );
        }
        
        return $item;
    }
    
    /**
     * Attempt to get the items in a filtered list that immediately flank a certain one
     * 
     * @param unknown $id
     * @param array $query_params
     * @return multitype:NULL Ambigous <NULL, \Dsc\Mongo\Collection> Ambigous <NULL, \Dsc\Mongo\Collection, unknown>
     */
    public static function surrounding( $id, array $query_params=array() )
    {
        $return = array(
        	'found' => false,
        	'prev' => null,
            'next' => null
        );
        
        $model = new static;
        foreach ($query_params as $key=>$value)
        {
            $model->setParam($key, $value);
        }
        	
        $model->__cursor = $model->collection()->find($model->conditions(), $model->fields());
        	
        if ($model->getParam('sort')) {
            $model->__cursor->sort($model->getParam('sort'));
        }
        if ($model->getParam('skip')) {
            $model->__cursor->skip($model->getParam('skip'));
        }
        	
        $found = false;
        $prev = null;
        $next = null;
        foreach ($model->__cursor as $doc)
        {
            // if the doc is the one we're looking for, get the next one, then break
            if ((string) $doc['_id'] == (string) $id)
            {
            	$found = true;
                if ($nextDoc = $model->__cursor->getNext()) {
                    $next = new static( $nextDoc );
                } else {
                	$total = $model->collection()->count( $model->conditions() );
                	if ($total > $model->getParam('skip') && $model->getParam('limit')) {
                		$skip = $model->getParam('skip') + $model->getParam('limit');
                		
                		$model->__cursor = $model->collection()->find($model->conditions(), $model->fields());
                		if ($model->getParam('sort')) {
                			$model->__cursor->sort($model->getParam('sort'));
                		}
                		$model->__cursor->limit(1);
                		$model->__cursor->skip($skip);
                		if ($model->__cursor->hasNext()) {
                			$query_params['skip'] = $skip;
                			\Dsc\System::instance()->get('session')->trackState( get_class( $model ), $query_params );                			
                			$next = new static( $model->__cursor->getNext() );
                		}
                	}
                }
                
                // If this is the first doc in the list (if $prev == null),
                if (!empty($next) && empty($prev)) 
                {
                	// and if this is a paginated set, and if we're not on page 1,                	
                	if ($model->getParam('skip') > $model->getParam('limit')) 
                	{
                		// try to load the previous page.  Set the new page as the new state
                		$skip = $model->getParam('skip') - $model->getParam('limit');
                		
                		$model->__cursor = $model->collection()->find($model->conditions(), $model->fields());
                		if ($model->getParam('sort')) {
                			$model->__cursor->sort($model->getParam('sort'));
                		}
                		$model->__cursor->limit(1);
                		$model->__cursor->skip($skip + ($model->getParam('limit')-1));
                		if ($model->__cursor->hasNext()) {
                			$prev = $model->__cursor->getNext();
                			$query_params['skip'] = $skip;
                			\Dsc\System::instance()->get('session')->trackState( get_class( $model ), $query_params );                			
                		}                		
                	}

                } 
                
                break;
            }
            // otherwise, set the doc as the prev and continue on
            else 
            {
            	$prev = $doc;
            }
        }

        if ($found) 
        {
        	if (!empty($prev))
        	{
        		$prev = new static( $prev );
        	}
        	
        	$return['found'] = $found;
        	$return['prev'] = $prev;
        	$return['next'] = $next;
        }
        
        return $return;
    }
    
    /**
     * Returns a pagination object
     * merged with a result set
     *
     * @return \Dsc\Pagination
     */
    public function paginate($refresh=false)
    {
        $offset = $this->getState('list.offset', 0, 'int');
        $this->setState('list.offset', $offset );
        
        $size = $this->getState('list.limit', 30, 'int');
        $this->setState('list.limit', $size );
        
        $this->setParam('limit', $size);
        $this->setParam('skip', $offset * $size);
        
        $total = $this->collection()->count( $this->conditions($refresh) );
        $result = new \Dsc\Pagination( $total, $size );
        $result->items = $this->getItems($refresh);
    
        return $result;
    }
    
    /**
     * Gets the count of items that match the current set conditions
     * 
     * @return number
     */
    public function getCount()
    {
        $total = $this->collection()->count( $this->conditions() );
        
        return (int) $total;
    }
    
    /**
     * Gets the array of fields set to be returned by the next query,
     * fetching them if necessary
     *  
     * @return array
     */
    public function fields($refresh=false)
    {
        if (empty($this->__query_params['fields']) || $refresh) 
        {
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
    public function conditions($refresh=false)
    {
        if (empty($this->__query_params['conditions']) || $refresh) 
        {
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
        if (!empty($filter_id))
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
        
        $filter_ids_excluded = $this->getState('filter.ids_excluded');
        if (!empty($filter_ids_excluded) && is_array($filter_ids_excluded))
        {
            $_ids = array();
            foreach ($filter_ids_excluded as $_filter_id)
            {
                $_ids[] = new \MongoId( (string) $_filter_id);
            }
            $this->setCondition('_id', array('$nin' => $_ids) );
        }
        
        return $this;
    }
    
    /**
     * Gets the global Mongo connection 
     */
    public function getDb()
    {
        $mongo = \Dsc\System::instance()->get('mongo');
        if (is_a($mongo, '\MongoDB')) {
            return $mongo;
        } else if (is_a($mongo, '\Dsc\Mongo\Db')) {
            return $mongo->db();
        }
        
        return $mongo;
    }
    
    /**
     * This is static so you can do 
     * YourModel::collection()->find() or anything else with the MongoCollection object
     */
    public static function collection()
    {
        $item = new static();
        return $item->getDb()->selectCollection( $item->collectionName() );
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
        $model = new static();

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
     * Finds items in the collection based on set conditions
     *
     * @param unknown $conditions
     * @param unknown $fields
     */
    public static function findOne( $conditions=array(), $fields=array() )
    {
        $model = new static();
    
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
    
        return $model->getItem();
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
     * Refresh $this by reloading it from the database
     *
     * @param array $conditions
     * @param array $fields
     * @param array $sort
     * @return \Dsc\Mongo\Collection
     */
    public function reload()
    {
        if (empty($this->id)) {
        	return $this;
        }
        
        return $this->load( array(
        	'_id' => new \MongoId( (string) $this->id )
        ) );
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
        // preserve any key=>values from the original item that are not in the new document array 
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
                
        return $this;
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
    public function remove($options=array())
    {
        $this->__options = $options;
        
        $this->beforeDelete();
        
        $this->__last_operation = $this->collection()->remove(
                array('_id'=> new \MongoId((string) $this->get('id') ) )
        );
        
        $this->afterDelete();
        
        return $this;
    }
    
    /**
     * 
     * @return boolean|\Dsc\Mongo\Collection
     */
    public function validate()
    {
        return $this->checkErrors();
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
        if (empty($this->__options['skip_listeners'])) 
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'beforeValidate' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
            if ($event->isStopped()) {
                $this->setError( $event->getArgument('error') );
            }
        }
                
        return $this->checkErrors();
    }
    
    protected function beforeSave()
    {
        if (empty($this->__options['skip_listeners'])) 
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'beforeSave' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
            if ($event->isStopped()) {
                $this->setError( $event->getArgument('error') );
            }
        }
                
        return $this->checkErrors();
    }
    
    protected function beforeCreate()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'beforeCreate' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
            if ($event->isStopped()) {
                $this->setError( $event->getArgument('error') );
            }
        }        
                
        return $this->checkErrors();
    }
    
    protected function beforeUpdate()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'beforeUpdate' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
            if ($event->isStopped()) {
                $this->setError( $event->getArgument('error') );
            }            
        }        
        
        return $this->checkErrors();
    }
    
    protected function beforeDelete()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'beforeDelete' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
            if ($event->isStopped()) {
                $this->setError( $event->getArgument('error') );
            }            
        }
                
        if(!empty($this->__enable_trash)) {
        	\Dsc\Mongo\Collections\Trash::trash($this);
        }
        
        return $this->checkErrors();
    }

    protected function afterSave()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'afterSave' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        }        
    }
    
    protected function afterCreate()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'afterCreate' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);            
        }	
    }
    
    protected function afterUpdate()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'afterUpdate' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        }
    }
    
    protected function afterDelete()
    {
        if (empty($this->__options['skip_listeners']))
        {
            $eventNameSuffix = $this->inputFilter()->clean(get_class($this), 'ALNUM');
            $event = (new \Joomla\Event\Event( 'afterDelete' . $eventNameSuffix ))->addArgument('model', $this);
            $event = \Dsc\System::instance()->getDispatcher()->triggerEvent($event);
        }
    }

    /**
     * Store the model document directly to the database 
     * without firing plugin events
     * 
     * @param unknown $document
     * @param unknown $options
     * @return \Dsc\Mongo\Collection
     */
    public function store( $options=array() )
    {
        if ($this->_id) 
        {
            $this->__options = $options + array(
                'upsert'=>true,
                'multiple'=>false,
                'w'=>0
            );
            
            $this->__last_operation = $this->collection()->update(
                array( '_id'=> new \MongoId( $this->get('id') ) ),
                $this->cast(),
                $this->__options
            );        	
        } 
        else 
        {
            $this->set('_id', new \MongoId );
                        
            $this->__options = $options + array(
                'w'=>0
            );
            
            $this->__last_operation = $this->collection()->insert(
                $this->cast(),
                $this->__options
            );        	
        }
    
        return $this;
    }
    
    /**
     * Preferably returns a \Search\Models\Item
     * but just returns $this otherwise 
     * 
     * @return $this|\Search\Models\Item
     */
    public function toSearchItem()
    {
        if (class_exists('\Search\Models\Item')) 
        {
            return new \Search\Models\Item( $this->cast() );
        }
        
        return $this;
    }
    
    /**
     * Preferably returns a \Search\Models\Item
     * but just returns $this otherwise
     *
     * @return $this|\Search\Models\Item
     */
    public function toAdminSearchItem()
    {
        if (class_exists('\Search\Models\Item')) 
        {
            return new \Search\Models\Item( $this->cast() );
        }
        
        return $this;
    }

    /**
     * Determines if a string is a valid MongoId
     * 
     * @param unknown $string
     * @return boolean
     */
    public static function isValidId( $string )
    {
        $regex = '/^[0-9a-z]{24}$/';
        if (preg_match($regex, (string) $string))
        {
            return true;
        }        
        
        return false;
    }
}
