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
                $this->tags = \Base::instance()->split( (string) $this->tags );
            }
        }
        
        return parent::beforeSave();
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public function getTags($types=array())
    {
        // TODO if $types, only get tags used by items of those types
        $tags = $this->collection()->distinct("tags");
        $tags = array_values( array_filter( $tags ) );
    
        return $tags;
    }
}