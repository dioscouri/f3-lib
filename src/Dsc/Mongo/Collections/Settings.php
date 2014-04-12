<?php 
namespace Dsc\Mongo\Collections;

class Settings extends \Dsc\Mongo\Collection 
{
    protected $__collection_name = 'common.settings';
    protected $__type = 'common.settings';
    
    /**
     * Returns the Settings object for the current __type
     */
    public static function fetch()
    {
        $item = new static;
        $item->load(array('type' => $item->type()));
        return $item;
    }
    
    /**
     * Gets the type
     */
    public function type()
    {
        return $this->__type;
    }

    /**
     * 
     */
    protected function beforeValidate()
    {
        if (empty($this->type))
        {
            $this->type = $this->__type;
        }
        
        if (!$this->get('metadata.created'))
        {
            $this->set('metadata.created', \Dsc\Mongo\Metastamp::getDate('now') );
        }
        
        $this->set('metadata.last_modified', \Dsc\Mongo\Metastamp::getDate('now') );
    
        return parent::beforeValidate();
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