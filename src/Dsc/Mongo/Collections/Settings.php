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
    
        return parent::beforeValidate();
    }
}