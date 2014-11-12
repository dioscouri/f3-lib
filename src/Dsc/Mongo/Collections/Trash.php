<?php
namespace Dsc\Mongo\Collections;

class Trash extends \Dsc\Mongo\Collections\Nodes
{
	
	public $collection;
	
    public $document;

    protected $__collection_name = 'common.trash';

    protected $__type = 'common.trash';

    protected $__config = array(
        'default_sort' => array(
            'metadata.created.time' => 1
        )
    );
    
    /**
     * Method to auto-populate the model state.
     *
     */
    public function populateState()
    {
        return parent::populateState();
    }

    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword&&is_string($filter_keyword))
        {
            $key = new \MongoRegex('/'.$filter_keyword.'/i');
            
            $where = array();
            
            $regex = '/^[0-9a-z]{24}$/';
            if (preg_match($regex, (string) $filter_keyword))
            {
                $where[] = array(
                    '_id' => new \MongoId((string) $filter_keyword)
                );
            }
  
            $where[] = array(
                'metadata.creator.name' => $key 
            );
            
            $this->setCondition('$or', $where);
        }
           
        return $this;
    }
	
    public static function trash($document) {
    	if($document instanceof \Dsc\Mongo\Collection ) {
    		$trash = new static;
    		$trash->set('instanceof', (string) get_class($document)) ;
    		$trash->set('collection', $document->collectionName());
    		$trash->set('document', $document->cast());
    		if(!empty($document->type)) {
    			$trash->set('type', $document->type);
    		} 
    		$trash->save();
    		
    	}
    	else {
    		//do something else if we are not using a mapper
    	}
    }
  	
    public function restore() {
    	$collection =  $this->getDb()->selectCollection($this->collection)->insert($this->document); 
    	//TODO add checks to make sure it succeeded
    	$this->remove();
    }
    
}