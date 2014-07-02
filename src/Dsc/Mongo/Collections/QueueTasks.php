<?php 
namespace Dsc\Mongo\Collections;

class QueueTasks extends \Dsc\Mongo\Collection 
{
    public $created;
    public $task;                   // callable
    public $parameters;             // array
    public $when;                   // time() after which the queue should be executed
    public $priority = 0;           // int
    public $batch = null;           // string, for categorization only
    public $locked_by = null;       // MongoId, identifies the daemon or cron job executing the task
    public $locked_at = 0;          // time() when daemon or cron job started processing this task
    
    protected $__collection_name = 'queue.tasks';
    protected $__config = array(
        'default_sort' => array(
            'created.time' => -1
        ),
    );
    
    public function complete()
    {
        $model = new \Dsc\Mongo\Collections\QueueArchives( $this->cast() );
        $model->completed = \Dsc\Mongo\Metastamp::getDate( 'now' );
        
        try {
            $model->save();
            $this->remove();
        }
        catch (\Exception $e) 
        {
            
        }
        
        return $this;
    }
    
    public static function add( $task, $parameters, $when=null, $priority=0, $batch=null )
    {
        $model = new static;

        $model->created = \Dsc\Mongo\Metastamp::getDate( 'now' );
        $model->task = $task;
        $model->parameters = $parameters;
        $model->when = $when ? (int) $when : time();
        $model->priority = (int) $priority;
        $model->batch = $batch;
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
            }
            $where[] = array('task'=>$key);
        
            $this->setCondition('$or', $where);
        }        
        
        $filter_priority = $this->getState('filter.priority');
        if (strlen($filter_priority))
        {
            $this->setCondition('priority', $filter_priority);
        }
        
        $filter_task = $this->getState('filter.task');
        if (strlen($filter_task))
        {
            $this->setCondition('task', $filter_task);
        }
        
        return $this;
    }
    
    /**
     *
     * @param array $query
     * @return unknown
     */
    public static function distinctBatches($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("batch", $query);
        $distinct = array_values( array_filter( $distinct ) );
        
        sort($distinct);
        
        return $distinct;
    }    
    
    /**
     *
     * @param array $query
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
}