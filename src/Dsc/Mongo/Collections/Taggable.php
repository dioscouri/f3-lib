<?php 
namespace Dsc\Mongo\Collections;

class Taggable extends \Dsc\Mongo\Collections\Nodes 
{
    /**
     * Default Document Structure
     * @var unknown
     */
    public $tags = array();
    
    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $filter_tag = $this->getState('filter.tag');
        if (strlen($filter_tag))
        {
            $this->setCondition('tags', $filter_tag);
        }
        
        return $this;
    }
    
    protected function beforeSave()
    {
        if (!empty($this->tags) && !is_array($this->tags))
        {
            $this->tags = trim($this->tags);
            if (!empty($this->tags)) {
                $this->tags = array_map(function($el){
                	return strtolower($el);
                }, \Base::instance()->split( (string) $this->tags ));
            }
        }
        elseif(empty($this->tags) && !is_array($this->tags)) 
        {
            $this->tags = array();
        }        
                
        return parent::beforeSave();
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function getTags($query=array())
    {
        if (empty($this)) {
            $model = new static();
        } else {
            $model = clone $this;
        }
        
        $tags = $model->collection()->distinct("tags", $query);
        $tags = array_values( array_filter( $tags ) );
    
        return $tags;
    }
}