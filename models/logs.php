<?php 
namespace Dsc\Models;

class Logs extends \Dsc\Models\Db\Mongo 
{
    protected $collection = 'common.logs';
    protected $default_ordering_direction = '-1';
    protected $default_ordering_field = 'datetime';
    
    public function __construct($config=array())
    {
        $config['filter_fields'] = array(
            'datetime', 'priority', 'category'
        );
        $config['order_directions'] = array('1', '-1');
        
        parent::__construct($config);
    }
    
    public function add( $message, $priority='INFO', $category='General' )
    {
        $mapper = $this->getMapper();
        $mapper->reset();
        $mapper->datetime = date('Y-m-d H:i:s');
        $mapper->priority = $priority;
        $mapper->category = $category;
        $mapper->message = $message;
        
        $mapper->save();
        
        return $mapper;
    }
    
    protected function fetchFilters()
    {
        $this->filters = array();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('priority'=>$key);
            $where[] = array('category'=>$key);
    
            $this->filters['$or'] = $where;
        }
    
        $filter_id = $this->getState('filter.id');
        if (strlen($filter_id))
        {
            $this->filters['_id'] = new \MongoId((string) $filter_id);
        }
        
        $filter_category_contains = $this->getState('filter.category-contains');
        if (strlen($filter_category_contains))
        {
            $key =  new \MongoRegex('/'. $filter_category_contains .'/i');
            $this->filters['category'] = $key;
        }
        
        return $this->filters;
    }
}