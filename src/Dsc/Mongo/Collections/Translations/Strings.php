<?php
namespace Dsc\Mongo\Collections\Translations;

class Strings extends \Dsc\Mongo\Collections\Settings
{
    protected $__collection_name = 'translations.strings';
    protected $__type = 'strings';
    
    public $language_code = null;
    public $language_id = null;
    public $strings = array();

    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $this->setCondition( 'type', $this->__type );
        
        $filter_lang = $this->getState('filter.lang');
        if (strlen($filter_lang))
        {
            $this->setCondition('language_code', $filter_lang);
        }
        
        $filter_lang_id = $this->getState('filter.lang_id');
        if (strlen($filter_lang_id))
        {
            $this->setCondition('language_id', $filter_lang_id);
        }
        
        return $this;
    }
}