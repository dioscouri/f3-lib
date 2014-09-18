<?php
namespace Dsc\Traits\Models;

trait Describable
{
    /*
     * Adds these to the document
     */
    public $title; // string INDEX
    public $slug; // string INDEX
    public $description; // text
    
    protected function describableFetchConditions()
    {
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('title'=>$key);
            $where[] = array('slug'=>$key);
            $where[] = array('description'=>$key);
    
            $this->setCondition('$or', $where);
        }
    
        $filter_slug = $this->getState('filter.slug');
        if (strlen($filter_slug))
        {
            $this->setCondition('slug', $filter_slug);
        }
    
        $filter_title = $this->getState('filter.title');
        if (strlen($filter_title))
        {
            $this->setCondition('title', $filter_title);
        }
    
        return $this;
    }
    
    protected function describableBeforeValidate()
    {
        if (empty($this->slug) && !empty($this->title))
        {
            $this->slug = $this->describableGenerateSlug();
        }
    
        return parent::beforeValidate();
    }
    
    public function describableValidate()
    {
        if (empty($this->title)) {
            $this->setError('Title is required');
        }
    
        return parent::validate();
    }
    
    /**
     *
     * @param string $unique
     * @return string
     */
    public function describableGenerateSlug( $unique=true )
    {
        if (empty($this->title)) {
            $this->setError('A title is required for generating the slug');
            return $this->checkErrors();
        }
    
        $slug = \Web::instance()->slug( $this->title );
    
        if ($unique)
        {
            $base_slug = $slug;
            $n = 1;
    
            while ($this->describableSlugExists($slug))
            {
                $slug = $base_slug . '-' . $n;
                $n++;
            }
        }
    
        return $slug;
    }
    
    /**
     *
     *
     * @param string $slug
     * @return unknown|boolean
     */
    public function describableSlugExists( $slug )
    {
        $clone = (new static)->load(array('slug'=>$slug, 'type'=>$this->__type));
    
        if (!empty($clone->id)) {
            return $clone;
        }
    
        return false;
    }
}