<?php 
namespace Dsc\Mongo\Collections;

class QueueTasks extends \Dsc\Mongo\Collection 
{
    public $created;
    public $title;                  // [optional] title of task, for display and search purposes
    public $task;                   // callable
    public $parameters = array();   // array
    public $when;                   // time() after which the queue should be executed
    public $priority = 0;           // int
    public $batch = null;           // string, for categorization only
    public $locked_by = null;       // MongoId, identifies the daemon or cron job executing the task
    public $locked_at = 0;          // time() when daemon or cron job started processing this task
    
    protected $__collection_name = 'queue.tasks';
    protected $__config = array(
        'default_sort' => array(
            'when' => 1
        ),
    );
    
    public function complete($message=null)
    {

        try {
        	//remove the task from the queue
        	$this->remove();
        	//move to archive 
        	if($this->archive) {
	        	$model = new \Dsc\Mongo\Collections\QueueArchives( $this->cast() );
	        	$model->completed = \Dsc\Mongo\Metastamp::getDate( 'now' );
	        	$model->message = $message;
	            $model->save();
        	}
        	
            
        }
        catch (\Exception $e) 
        {
            throw new \Exception($e->getMessage());
        }
        
        return $this;
    }
    
    public function error($message=null)
    {
        $model = new \Dsc\Mongo\Collections\QueueArchives( $this->cast() );
        $model->completed = \Dsc\Mongo\Metastamp::getDate( 'now' );
        $model->message = $message;
        $model->status = 'error';
        
        try {
            $model->save();
            $this->remove();
        }
        catch (\Exception $e)
        {
    
        }
    
        return $this;
    }    
    
    /**
     * Adds an item to the queue
     *
     * Throws an Exception
     * 
     * @param unknown $task
     * @param unknown $parameters
     * @param unknown $options
     * @return \Dsc\Mongo\Collections\QueueTasks
     */
    public static function add( $task, $parameters=array(), $options=array() )
    {
        $options = $options + array(
            'title' => null,
            'when' => null,
            'priority' => 0,
            'batch' => null
        );
        
        $model = new static;

        $model->created = \Dsc\Mongo\Metastamp::getDate( 'now' );
        $model->title = $options['title'];
        $model->task = $task;
        $model->parameters = $parameters;
        $model->when = $options['when'] ? (int) $options['when'] : time();
        $model->priority = (int) $options['priority'];
        $model->batch = $options['batch'];
        
        $sparse_options = $options;
        unset($sparse_options['title']);
        unset($sparse_options['when']);
        unset($sparse_options['priority']);
        unset($sparse_options['batch']);
        
        $model->options = $sparse_options;
        $model->validate()->store();
        
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
            $where[] = array('title'=>$key);
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
    
    public function title()
    {
        return $this->title ? $this->title : $this->task;
    }
    
    public function validate()
    {
        if (empty($this->task)) {
            $this->setError('Task is required');
        }
        
        return parent::validate();
    }
}