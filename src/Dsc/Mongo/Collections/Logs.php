<?php 
namespace Dsc\Mongo\Collections;

class Logs extends \Dsc\Mongo\Collection 
{
    public $created;
    public $priority;
    public $category;
    public $message;
    
    protected $__collection_name = 'common.logs';
    protected $__config = array(
        'default_sort' => array(
            'created.microtime' => -1
        ),
    );
    
    public static function add( $message, $priority='INFO', $category='General' )
    {
        $model = new static;

        $model->created = \Dsc\Mongo\Metastamp::getDate( 'now' );
        $model->set('created.microtime', microtime( true ) );
        $model->priority = $priority;
        $model->category = $category;
        $model->message = $message;
        
        $model->save();
        
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
            $where[] = array('message'=>$key);
            $where[] = array('priority'=>$key);
            $where[] = array('category'=>$key);
        
            $this->setCondition('$or', $where);
        }        
        
        $filter_priority = $this->getState('filter.priority');
        if (strlen($filter_priority))
        {
            $this->setCondition('priority', $filter_priority);
        }
        
        $filter_category = $this->getState('filter.category');
        if (strlen($filter_category))
        {
            $this->setCondition('category', $filter_category);
        }
        
        return $this;
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctCategories($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("category", $query);
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
}