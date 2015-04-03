<?php 
namespace Dsc\Mongo\Collections\Translations;

class Keys extends \Dsc\Mongo\Collection 
{
    protected $__collection_name = 'translations.keys';
    
    public $title; // string INDEX
    public $slug; // string INDEX

    protected function fetchConditions()
    {
        parent::fetchConditions();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('title'=>$key);
            $where[] = array('slug'=>$key);
    
            $this->setCondition('$or', $where);
        }
    
        $filter_slug = $this->getState('filter.slug');
        if (strlen($filter_slug))
        {
            $this->setCondition('slug', strtolower($filter_slug));
        }
    
        $filter_title = $this->getState('filter.title');
        if (strlen($filter_title))
        {
            $this->setCondition('title', $filter_title);
        }
    
        return $this;
    }
    
    protected function beforeValidate()
    {
        if (empty($this->slug) && !empty($this->title))
        {
            $this->slug = $this->generateSlug();
        }
    
        return parent::beforeValidate();
    }
    
    protected function beforeCreate()
    {
        if (empty($this->slug) || $this->slugExists($this->slug))
        {
            $this->setError('Slug already exists');
        }
    
        return parent::beforeCreate();
    }
    
    public function validate()
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
    public function generateSlug( $unique=true )
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
    
            while ($this->slugExists($slug))
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
    public function slugExists( $slug )
    {
        $clone = (new static)->load(array('slug'=>$slug));
    
        if (!empty($clone->id)) {
            return $clone;
        }
    
        return false;
    }
}