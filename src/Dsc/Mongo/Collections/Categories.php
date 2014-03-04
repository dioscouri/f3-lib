<?php 
namespace Dsc\Mongo\Collections;

class Categories extends \Dsc\Mongo\Collections\Nodes 
{
    /**
     * Default Document Structure
     * @var unknown
     */
    public $title; // string INDEX
    public $slug; // string INDEX
    public $description; // text
    public $parent; // MongoId(),
    public $path; // '/path/to/the/current/category/using/slugs' INDEX UNIQUE
    public $ancestors = array(); // array( array('MongoId', 'slug', 'title') )
    
    protected $__collection_name = 'common.categories';
    protected $__type = 'common.categories';
    protected $__config = array(
        'default_sort' => array(
            'path' => 1
        ),
    );
    
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
            $where[] = array('path'=>$key);
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
        
        $filter_parent = $this->getState('filter.parent');
        if (!empty($filter_parent))
        {
            $this->setCondition('parent', new \MongoId((string) $filter_parent));
        }
    
        return $this;
    }
    
    public function validate()
    {
        if (empty($this->title)) {
            $this->setError('Title is required');
        }
        
        // is the path unique?
        // this would be a great case for $this->validateWith( $validator ); -- using a Uniqueness Validator
        if ($existing = $this->pathExists( $this->path )) 
        {
            if ((empty($this->_id) || $this->_id != $existing->_id) && $existing->type == $this->type) 
            {
                $this->setError('An item with this title already exists with this parent.');
            }            
        }

        return parent::validate();
    }
    
    protected function beforeSave()
    {
        if (empty($this->type)) {
            $this->type = $this->__type;
        }
    
        if (empty($this->slug))
        {
            $this->slug = \Joomla\Filter\OutputFilter::stringURLUnicodeSlug($this->title);
        }
    
        if (empty($this->parent) || $this->parent == "null")
        {
            $this->parent = null;
        } else {
            $this->parent = new \MongoId((string) $this->parent);
        }
        
        if (empty($this->path))
        {
            $this->path = $this->generatePath( $this->slug, $this->parent );
        }
        
        if (empty($this->ancestors) && !empty($this->parent))
        {
            $parent_title = null;
            $parent_slug = null;
            $parent_path = null;
            $parent_ancestors = array();
            
            $parent = $this->emptyState()->setState('filter.id', $this->parent)->getItem();
            if (!empty($parent->title))
            {
                $parent_title = $parent->title;
            }
            if (!empty($parent->slug))
            {
                $parent_slug = $parent->slug;
            }
            if (!empty($parent->path))
            {
                $parent_path = $parent->path;
            }
            if (!empty($parent->ancestors))
            {
                $parent_ancestors = $parent->ancestors;
            }
            
            $this->ancestors = $parent_ancestors;
            $this->ancestors[] = array(
                'id' => $this->parent,
                'slug' => $parent_slug,
                'title' => $parent_title
            );
        }        
        
        return parent::beforeSave();
    }
    
    public function beforeUpdate()
    {
        // if this item's parent is different from it's parent in the database, then we also need to update all the children
        $old = $this->load(array('_id' => $this->_id ));
        if ($old->parent != $this->parent || $old->title != $this->title) {
            // update children after save
            $this->__options['update_children'] = true;
        }
        
        return parent::beforeUpdate();
    }
    
    public function afterUpdate()
    {
        if (!empty($this->__options['update_children']))
        {
            if ($children = $this->emptyState()->setState('filter.parent', $updated->id)->getItems())
            {
                foreach ($children as $child)
                {
                    unset($child->ancestors);
                    unset($child->path);
                    $child->update(array(), array('update_children' => true));
                }
            }
        }
    }
    
    public function generatePath( $slug, $parent_id=null )
    {
        $path = null;
        
        if (empty($parent_id)) {
            return "/" . $slug;
        }
        
        // get the parent's path, append the slug
        $parent = $this->emptyState()->setState('filter.id', $parent_id)->getItem();
        $this->emptyState();
        
        if (!empty($parent->path)) {
            $path = $parent->path;
        }
        
        $path .= "/" . $slug;
        
        return $path; 
    }
    
    public function pathExists( $path )
    {
        $item = $this->load(array('path'=>$path, 'type'=>$this->__type));
        
        if (!empty($item->_id)) {
            return $item;
        }
        
        return false;
    }

}