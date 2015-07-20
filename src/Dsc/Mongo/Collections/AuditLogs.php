<?php 
namespace Dsc\Mongo\Collections;

class AuditLogs extends \Dsc\Mongo\Collection 
{
    public $created;
    public $priority;
    public $category;
    public $message;    
    public $resource_type;
    public $resource_id;
    public $action;
    public $diff;
    public $actor_type; // user | worker (cli process)
    public $actor_name; // display name of the user/worker
    public $actor_id; // id of the user/worker
    
    protected $__collection_name = 'audit.logs';
    
    protected $__config = array(
        'default_sort' => array(
            'created.microtime' => -1
        ),
    );
    
    public static $__indexes = array(
    	['created.microtime' => -1],
        ['message' => 1],
        ['priority' => 1],
        ['category' => 1],
        ['resource_type' => 1],
        ['resource_id' => 1],
        ['action' => 1],
        ['diff' => 1],
        ['actor_type' => 1],
        ['actor_name' => 1],
        ['actor_id' => 1],
    );    
    
    /**
     * 
     * @param array $resource
     * @param string $action
     * @param string $diff
     * @param array $actor
     * @param string $message
     * @param string $priority
     * @param string $category
     * 
     * @return \Dsc\Mongo\Collections\AuditLogs
     */
    public static function add( $resource, $action, $diff, $actor, $message=null, $priority='INFO', $category='Audit' )
    {
        $model = new static;

        $model->created = \Dsc\Mongo\Metastamp::getDate( 'now' );
        $model->set('created.microtime', microtime( true ) );
        $model->priority = $priority;
        $model->category = $category;
        $model->message = $message;
        
        $model->resource_type = !empty($resource['type']) ? $resource['type'] : null;
        $model->resource_id = !empty($resource['id']) ? $resource['id'] : null;
        $model->action = $action;
        $model->diff = $diff;
        $model->actor_type = !empty($actor['type']) ? $actor['type'] : null;
        $model->actor_id = !empty($actor['id']) ? $actor['id'] : null;
        $model->actor_name = !empty($actor['name']) ? $actor['name'] : null;
        
        $model->store();
        
        return $model;
    }
    
    protected function fetchConditions()
    {
        parent::fetchConditions();

        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
        
            $where = array();
        
            $regex = '/^[0-9a-z]{24}$/';
            if (preg_match($regex, (string) $filter_keyword))
            {
                $where[] = array('_id'=>new \MongoId((string) $filter_keyword));
                $where[] = array('resource_id'=>new \MongoId((string) $filter_keyword));
                $where[] = array('actor_id'=>new \MongoId((string) $filter_keyword));
            }
            $where[] = array('message'=>$key);
            $where[] = array('priority'=>$key);
            $where[] = array('category'=>$key);
            
            $where[] = array('resource_type'=>$key);
            $where[] = array('action'=>$key);
            $where[] = array('diff'=>$key);
            $where[] = array('actor_type'=>$key);
            $where[] = array('actor_name'=>$key);
            
        
            $this->setCondition('$or', $where);
        }        
        
        $filter_resource_type = $this->getState('filter.resource_type');
        if (strlen($filter_resource_type))
        {
            $this->setCondition('resource_type', $filter_resource_type);
        }
        
        $filter_resource_id = $this->getState('filter.resource_id');
        if (strlen($filter_resource_id))
        {
            $this->setCondition('resource_id', $filter_resource_id);
        }
        
        $filter_action = $this->getState('filter.action');
        if (strlen($filter_action))
        {
            $this->setCondition('action', $filter_action);
        }
        
        $filter_actor_type = $this->getState('filter.actor_type');
        if (strlen($filter_actor_type))
        {
            $this->setCondition('actor_type', $filter_actor_type);
        }
        
        $filter_actor_id = $this->getState('filter.actor_id');
        if (strlen($filter_actor_id))
        {
            $this->setCondition('actor_id', $filter_actor_id);
        }
        
        return $this;
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctResourceTypes($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("resource_type", $query ? $query : null);
        $distinct = array_values( array_filter( $distinct ) );
        
        sort($distinct);
        
        return $distinct;
    }    
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctActions($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("action", $query ? $query : null);
        $distinct = array_values( array_filter( $distinct ) );
    
        sort($distinct);
    
        return $distinct;
    }
    
    /**
     * 
     * @param unknown $query
     * @return unknown
     */
    public static function distinctActorTypes($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("actor_type", $query ? $query : null);
        $distinct = array_values( array_filter( $distinct ) );
    
        sort($distinct);
    
        return $distinct;
    }
}