<?php 
namespace Dsc\Mongo\Collections;

class Categories extends \Dsc\Mongo\Collections\Nodes 
{
    use \Dsc\Traits\Models\Ancestors;
    use \Dsc\Traits\Models\ForSelection;
    use \Dsc\Traits\Models\Seo;
    
    /**
     * Default Document Structure
     * @var unknown
     */
    public $title; // string INDEX
    public $slug; // string INDEX
    public $description; // text
    //public $parent; // MongoId(),
    //public $path; // '/path/to/the/current/category/using/slugs' INDEX UNIQUE
    //public $ancestors = array(); // array( array('MongoId', 'slug', 'title') )
    
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
        
        $this->ancestorsFetchConditions();
    
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
        
        $filter_path = $this->getState('filter.path');
        if (strlen($filter_path))
        {
            // if the path doesn't begin with /, prefix it with a /
            if (substr($filter_path, 0, 1) !== '/') 
            {
                $filter_path = '/' . $filter_path;
            }
            $this->setCondition('path', $filter_path);
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
    
        if (empty($this->parent) || $this->parent == "null")
        {
            $this->parent = null;
        } else {
            $this->parent = new \MongoId((string) $this->parent);
        }
        
        $this->path = $this->generatePath( $this->slug, $this->parent );
        
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

		if (empty($this->parent) || $this->parent == "null")
        {
            $this->ancestors = array();
        }
        return parent::beforeSave();
    }
    
    protected function beforeUpdate()
    {
        // if this item's parent is different from it's parent in the database, then we also need to update all the children
        $old = (new static)->load(array('_id' => $this->_id ));
        if ($old->parent != $this->parent || $old->title != $this->title) {
            // update children after save
            $this->__options['update_children'] = true;
        }
        return parent::beforeUpdate();
    }
    
    protected function afterUpdate()
    {
        if (!empty($this->__options['update_children']))
        {
            if ($children = (new static())->setState('filter.parent', $this->_id)->getItems())
            {
                foreach ($children as $child)
                {
                    $child->ancestors = array();
                    $child->path = null;
                    $child->update(array(), array('update_children' => true));
                }
            }
        }
        
        parent::afterUpdate();
    }
    
    public function generatePath( $slug, $parent_id=null )
    {
        $path = null;
        
        if (empty($parent_id)) {
            return "/" . $slug;
        }
        
        // get the parent's path, append the slug
        $parent = (new static())->setState('filter.id', $parent_id)->getItem();
        
        if (!empty($parent->path)) {
            $path = $parent->path;
        }
        
        $path .= "/" . $slug;
        
        return $path; 
    }
    
    public function pathExists( $path )
    {
        $item = (new static)->load(array('path'=>$path, 'type'=>$this->__type));
        
        if (!empty($item->_id)) {
            return $item;
        }
        
        return false;
    }
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
            $parent = null;
            if( isset( $this->parent ) && $this->parent != 'null'){
            	$parent = $this->parent;
            }
            
            while ($this->slugExists($slug, $parent))
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
    public function slugExists( $slug, $parent = null )
    {
    	$conditions = array('slug'=>$slug);
        if (!empty($parent)) 
        {
        	$conditions['parent'] = new \MongoId($parent);
        }
        if (!empty($this->_type))
        {
        	$conditions['type'] = $this->_type;
        }
        $clone = (new static())->load($conditions);
        
        if (!empty($clone->id)) {
            return $clone;
        }
    
        return false;
    }
    
    /**
     * Determines the depth of this model in the tree
     *
     * @return number
     */
    public function getDepth()
    {
        if (!isset($this->depth))
        {
            $this->depth = substr_count( $this->path, "/" );
        }
    
        return (int) $this->depth;
    }
    
    /**
     * Gets the ancestors of an item
     * 
     * @return array
     */
    public function ancestors()
    {
        $return = array();
        
        $item = $this;
        while (!empty($item->parent)) 
        {
        	$clone = (new static)->load(array('_id'=>new \MongoId( (string) $item->parent) ));
        	unset($item);
        	if (!empty($clone->id)) 
        	{
        	    array_unshift($return, $clone);
        	    if (!empty($clone->parent)) {
        	        $item = $clone;
        	    }
        	}
        }
        
        return $return;
    }
    
    /**
     * Gets the child categories
     *
     * @return array
     */
    public function getChildCategories()
    {
    	return (new static)->setState('filter.parent',$this->id)->getList();
    }
    
}