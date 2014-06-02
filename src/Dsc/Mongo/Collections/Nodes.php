<?php 
namespace Dsc\Mongo\Collections;

class Nodes extends \Dsc\Mongo\Collection 
{
    /**
     * Default Document Structure
     * @var unknown
     */
    public $type; // string INDEX
    public $metadata = array(
    	'creator'=>null,
        'created'=>null,
        'last_modified'=>null
    );
    
    protected $__collection_name = 'common.content';
    protected $__type = 'common.content';
    protected $__config = array(
        'default_sort' => array(
            'metadata.created.time' => 1
        ),
    );
    
    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $filter_type = $this->getState('filter.type');
        if ($filter_type) {
            if (is_bool($filter_type) && $filter_type) {
                $this->setCondition('type', $this->__type );
            } elseif (strlen($filter_type)) {
                $this->setCondition('type', $filter_type );
            }
        }
        
        $filter_types = $this->getState('filter.types');
        if (!empty($filter_types) && is_array($filter_types))
        {
            $this->setCondition('types', array('$in' => $filter_types) );
        }
        
        $filter_creator_id = $this->getState('filter.creator.id');
        if (strlen($filter_creator_id))
        {
            $this->setCondition('metadata.creator.id', new \MongoId( (string) $filter_creator_id ) );
        }
        
        // TODO Add date-range filters for created & last_modified
        
        return $this;
    }
    
    protected function beforeValidate()
    {
        if (!$this->get('metadata.creator')) 
        {
            $identity = \Dsc\System::instance()->get('auth')->getIdentity();
            if (!empty($identity->id)) 
            {
            	$this->set('metadata.creator', array(
	                'id' => $identity->id,
	                'name' => $identity->fullName()
            	));
            }
            else 
            {
            	$this->set('metadata.creator', array(
	                'id' => new \MongoId(),
	                'name' => 'Unicorn Egg Eater'
            	));
            }
        }
        
        if (!$this->get('metadata.created'))
        {
        	$this->set('metadata.created', \Dsc\Mongo\Metastamp::getDate('now') );
        }
        
        $this->set('metadata.last_modified', \Dsc\Mongo\Metastamp::getDate('now') );
        
        if (empty($this->type))
        {
            $this->type = $this->__type;
        }
        
        return parent::beforeValidate();
    }
    
    /**
     * Gets the type
     */
    public function type()
    {
        return $this->__type;
    }
    
    /**
     * Gets the metadata.created field, creating it if it doesn't exist
     */
    public function created()
    {
        if (!$this->get('metadata.created'))
        {
            $this->set('metadata.created', \Dsc\Mongo\Metastamp::getDate('now') );
        }
        
        return $this->get('metadata.created');
    }
    
    /**
     * Gets the metadata.last_modified field, creating it if it doesn't exist
     */
    public function lastModified()
    {
        if (!$this->get('metadata.last_modified'))
        {
            $this->set('metadata.last_modified', \Dsc\Mongo\Metastamp::getDate('now') );
        }
        
        return $this->get('metadata.last_modified');    
    }
}