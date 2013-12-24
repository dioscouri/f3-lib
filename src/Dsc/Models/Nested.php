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
    
    public function reorder($options=array())
    {
        if (!empty($options['trees']) && is_array($options['trees'])) {
            foreach ($options['trees'] as $tree) 
            {
                $mapper = $this->getMapper()->rebuildTree( $tree );
            }
        }
    }
    
    public function moveUp( $mapper ) 
    {
        return $mapper->moveUp();
    }
    
    public function moveDown( $mapper )
    {
        return $mapper->moveDown();
    }
    
    public function getDescendants( $mapper )
    {
    	return $mapper->getDescendants( $mapper );
    }
}