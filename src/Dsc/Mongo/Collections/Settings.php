<?php 
namespace Dsc\Mongo\Collections;

class Settings extends \Dsc\Mongo\Collections\Nodes 
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
}