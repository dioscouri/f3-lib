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
        
        $filter_tag = trim($this->getState('filter.tag'));
        if (strlen($filter_tag))
        {
            $this->setCondition('tags', $filter_tag);
        }
        
        $filter_tags = (array) $this->getState('filter.tags');
        if (!empty($filter_tags)) 
        {
            $filter_tags = array_filter( array_values( $filter_tags ), function( $var ) {
                $var = trim($var);
                return !empty( $var ); 
            } );

            if (!empty($filter_tags)) {
            	if( count( $filter_tags ) == 1 && $filter_tags[0] == '--' ) {
            		$this->setCondition('tags', array( '$size' => 0 ) );
            	} else {
            		$this->setCondition('tags', array( '$in' => $filter_tags ) );
            	}
            	
            }
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
        return static::distinctTags($query);
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctTags($query=array())
    {
    	$model = new static();
    	$distinct = $model->collection()->distinct("tags", $query);
    	$distinct = array_values( array_filter( (array) $distinct ) );
    
    	return $distinct;
    }
   
}
