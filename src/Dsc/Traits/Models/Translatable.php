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
    
    public function lang()
    {
        $lang = null;
        
        if ($this->__lang) {
            $lang = $this->__lang;
        } else if ($this->language) {
            $lang = $this->language;
        }
        
        return $lang;
    }
    
    public function setLang( $code ) 
    {
        $this->__lang = $code;
        $this->language = $code;
        
        return $this;
    }
    
    public function type()
    {
        if ($lang = $this->lang()) 
        {
            $this->__type = $lang . '.' . $this->__type;
        }
        
        return $this->__type;
    }
    
    public function originalType()
    {
        $type = $this->__type;
        
        if ($lang = $this->lang()) 
        {
            $type = str_replace($lang.'.', '', $type);
        }
    
        return $type;
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
        
        $item = (new static)->load(array('type'=>$lang . '.' . $this->type(), 'slug' => $this->slug));
        
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
    
    public function getItems($refresh=false) 
    {
        $items = parent::getItems($refresh);
        
        if (!empty($items)) 
        {
            foreach ($items as $key=>$item) 
            {
                // does a translation exist?
                // if so, use it
                $model = (new static)->setState('filter.slug', $item->slug);
                if ($translated = $model->getItem($refresh)) 
                {
                    if ($translated->id != $item->id) {
                        $items[$key] = $translated;
                    }
                }
            }
        }
        
        return $items;
    }
}