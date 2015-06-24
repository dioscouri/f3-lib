<?php
namespace Dsc\Mongo\Collections;

class Content extends \Dsc\Mongo\Collections\Describable
{
    use \Dsc\Traits\Models\Publishable;
    
    public $copy;

    protected $__collection_name = 'common.content';

    protected $__type = 'common.content';

    protected $__config = array(
        'default_sort' => array(
            'metadata.created.time' => 1
        )
    );
    
    /**
     * Method to auto-populate the model state.
     *
     */
    public function populateState()
    {
        if ($this->getState('is.search')) 
        {
        	$this->setState('filter.published_today', true)->setState('filter.publication_status', 'published');
        }
        
        return parent::populateState();
    }

    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword&&is_string($filter_keyword))
        {
            $key = new \MongoRegex('/'.$filter_keyword.'/i');
            
            $where = array();
            
            $regex = '/^[0-9a-z]{24}$/';
            if (preg_match($regex, (string) $filter_keyword))
            {
                $where[] = array(
                    '_id' => new \MongoId((string) $filter_keyword)
                );
            }
            $where[] = array(
                'slug' => $key
            );
            $where[] = array(
                'title' => $key
            );
            $where[] = array(
                'copy' => $key
            );
            $where[] = array(
                'description' => $key
            );
            $where[] = array(
                'metadata.creator.name' => $key
            );
            
            $this->setCondition('$or', $where);
        }
        
        $filter_copy_contains = $this->getState('filter.copy-contains');
        if (strlen($filter_copy_contains))
        {
            $key = new \MongoRegex('/'.$filter_copy_contains.'/i');
            $this->setCondition('copy', $key);
        }
        
        $this->publishableFetchConditions();
        
        return $this;
    }

    public function validate()
    {
        if (empty($this->title))
        {
            $this->setError('Title is required');
        }
        
        if (empty($this->slug))
        {
            $this->setError('A slug is required');
        }
        
        if ($existing = $this->slugExists($this->slug))
        {
            if (empty($this->id)||$existing->id!=$this->id)
            {
                // An item with this slug already exists. Slugs must be unique.
                $this->slug = $this->generateSlug();
            }
        }
        
        return parent::validate();
    }

    protected function beforeSave()
    {
        $this->publishableBeforeSave();
        
        return parent::beforeSave();
    }
    
    /**
     * 
     * @param unknown $query
     */
    public static function distinctTags($query=array())
    {
        $query = $query + array(
        	'type' => (new static)->type()
        );
        
        return parent::distinctTags($query);
    }
    
    /**
     * This method returns an abstract of this content item.
     * Description field is given priority, after which the first paragraph is extracted.
     * If all else fails, return the copy.
     * 
     */
    public function getAbstract(array $options=array())
    {
        $abstract = $this->description;
    
        if (empty($abstract))
        {
            $abstract = $this->{'copy'};
    
            preg_match('%(<p[^>]*>.*?</p>)%i', $this->{'copy'}, $regs);
            if (count($regs))
            {
                $abstract = $regs[1];
            }
        }
        
        if (!empty($options['strip_tags'])) 
        {
            $allowable_tags = !empty($options['allowable_tags']) ? $options['allowable_tags'] : '<p><b><i><u>';
            $abstract = strip_tags($abstract, $allowable_tags);            
        }
    
        return $abstract;
    }
    
    public function createIndexes($indexes = null) {
    	$this->publishableCreateIndexes();
    	parent::createIndexes();
    }
    
}