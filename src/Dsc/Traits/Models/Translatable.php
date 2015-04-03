<?php
namespace Dsc\Traits\Models;

trait Translatable
{
    protected $__lang;
    protected $__fallback_translatable = true;
    protected $__skip_translatable = false;
    
    protected function translatableFetchConditions()
    {

    }
    
    public function setLang( $code ) 
    {
        $this->__lang = $code;
        $this->language = $code;
        
        return $this;
    }
    
    public function type()
    {
        if ($this->__lang) {
            $this->__type = $this->__lang . '.' . $this->__type;
        } else if ($this->language) {
            $this->__type = $this->language . '.' . $this->__type;
        }
        
        return $this->__type;
    }
    
    /**
     * Does a translation exist for this item?
     * 
     * @param unknown $lang
     * @return \Dsc\Traits\Models\Translatable|boolean|unknown
     */
    public function translationExists( $lang )
    {
        $default_lang = 'en';
        if ($lang == $default_lang) 
        {
            return $this;
        }
        
        $this->__original_type = $this->__type;
        
        $this->__type = $lang . '.' . $this->type();
        
        $this->conditions(true); // refresh the conditions        
        $item = parent::getItem();
        
        $this->__type = $this->__original_type;
        
        if (empty($item->id))
        {
            return false;
        }
        
        return $item;
    }
    
    /**
     * Get the original item
     * 
     * @return \Dsc\Traits\Models\Translatable|unknown
     */
    public function translationSource()
    {
        $default_lang = 'en';
        if (empty($this->language) || $this->language == $default_lang) {
            return $this;
        }
        
        $item = (new static)->load(array('type'=>$this->__type, 'slug' => $this->slug));        
        
        return $item;
    }
    
    /**
     * Get the translations available for this item
     */
    public function translations()
    {
        // SELECT ->language where slug = X and type = Y
    }
    
    public function getItem($refresh=false)
    {
        /**
         * Allow a model that extends \Content to skip the extra query
         */
        if (!empty($this->__skip_translatable)) {
            return parent::getItem($refresh);
        }
        
        $this->__original_type = $this->__type;
        
        // Has setLang($code) been explicitly set?
        if ($this->__lang) 
        {
            $lang = $this->__lang;
        }
        // if not, then use the default from the \Base                
        else 
        {
            $lang = \Base::instance()->get('lang');            
        }

        $default_lang = 'en';
        if ($lang && $lang != $default_lang)
        {
            //\FB::log( $lang . '.' . $this->type());
            $this->__type = $lang . '.' . $this->type();
            
            $item = parent::getItem($refresh);
            if (empty($item->id)) 
            {
                // TODO are we supposed to fallback to the default?
                // YES is the default behavior
                // but NO can be requested
                $fallback = $this->__fallback_translatable;
                if ($fallback) {
                    $this->__type = $this->__original_type;
                    $this->conditions(true); // refresh the conditions
                    $item = parent::getItem();
                }
            }
        }
        else 
        {
            $item = parent::getItem($refresh);
        }
        
        return $item;
    }
}