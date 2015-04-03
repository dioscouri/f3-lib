<?php 
namespace Dsc\Mongo\Collections\Translations;

class Languages extends \Dsc\Mongo\Collections\Nodes 
{
    use \Dsc\Traits\Models\OrderableCollection;
    
    protected $__collection_name = 'translations.languages';
    protected $__type = 'languages';

    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $this->setCondition( 'type', $this->__type );
        
        return $this;
    }
    
    protected function beforeSave()
    {
        $this->orderingBeforeSave();
    
        return parent::beforeSave();
    }
    
    protected function afterSave()
    {
        parent::afterSave();
         
        $this->compressOrdering();
    }
}