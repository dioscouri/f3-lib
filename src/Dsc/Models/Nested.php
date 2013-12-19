<?php 
namespace Dsc\Models;

abstract class Nested extends \Dsc\Models\Db\Mongo 
{
    protected $default_ordering_direction = '1';
    protected $default_ordering_field = 'lft';
    
    public function getMapper()
    {
        $mapper = null;
        if ($this->collection) {
            $mapper = new \Dsc\Mongo\Mappers\Nested( $this->getDb(), $this->getCollectionName() );
        }
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
            $where[] = array('title'=>$key);
            $where[] = array('description'=>$key);
            $where[] = array('slug'=>$key);
            $where[] = array('path'=>$key);
    
            $this->filters['$or'] = $where;
        }
    
        $filter_id = $this->getState('filter.id');
        if (strlen($filter_id))
        {
            $this->filters['_id'] = new \MongoId((string) $filter_id);
        }
        
        return $this->filters;
    }
    
    /**
     * An alias for the save command
     *
     * @param unknown_type $values
     * @param unknown_type $options
     */
    public function create( $values, $options=array() )
    {
        
        return parent::create( $values, $options );
    }
}