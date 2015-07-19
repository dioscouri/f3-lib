<?php 
namespace Dsc\Mongo\Collections;

class QueueArchives extends \Dsc\Mongo\Collection 
{
    public $created;
    public $title;                  // [optional] title of task, for display and search purposes
    public $task;                   // callable
    public $parameters;             // array
    public $when;                   // time() after which the queue should be executed
    public $priority = 0;           // int
    public $batch = null;           // string, for categorization only
    public $completed;
    public $status = null;          // string, null/error
    public $message;
    
    protected $__collection_name = 'queue.archives';
    protected $__config = array(
        'default_sort' => array(
            'completed.time' => -1
        ),
    );
    
    public static $__indexes = array(
    		['completed.time' => 1]
    );
    
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
            }
            $where[] = array('title'=>$key);
            $where[] = array('type'=>$key);
        
            $this->setCondition('$or', $where);
        }        
        
        $filter_priority = $this->getState('filter.priority');
        if (strlen($filter_priority))
        {
            $this->setCondition('priority', $filter_priority);
        }
        
        $filter_type = $this->getState('filter.type');
        if (strlen($filter_type))
        {
            $this->setCondition('type', $filter_type);
        }
        
        return $this;
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctTypes($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("type", $query);
        $distinct = array_values( array_filter( $distinct ) );
        
        sort($distinct);
        
        return $distinct;
    }    
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctPriorities($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("priority", $query);
        $distinct = array_values( array_filter( $distinct ) );
    
        sort($distinct);
    
        return $distinct;
    }
    
    public function title()
    {
        return $this->title ? $this->title : $this->task; 
    }
    
   
}