<?php 
namespace Dsc\Mongo\Collections;

class Settings extends \Dsc\Mongo\Collection 
{
    protected $__collection_name = 'common.settings';
    protected $__type = 'common.settings';
    
    /**
     * Returns the requested Settings Model
     * defaults to the current Model's __type
     * 
     * @param string $type
     * @return \Dsc\Mongo\Collections\Settings
     */
    public static function fetch($type=null)
    {
    	
        $item = new static;
        if (empty($type)) {
            $type = $item->type();
        }
        
        $name = strtolower(str_replace('\\','.', get_class($item) ).'.'.$type);
        
        $loaded = \Base::instance()->get('settings.'.$name);
        if($loaded) {
      	 $item = $loaded;
        } else {
        	$item->load(array('type' => $type));
        	\Base::instance()->set('settings.'.$name, $item);
        }
        
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