<?php
namespace Dsc\Traits\Models;

trait Ancestors
{

    public $parent; // MongoId(),
    public $path; // '/path/to/the/current/category/using/slugs' INDEX UNIQUE
    public $ancestors = array(); // array( array('MongoId', 'slug', 'title') )
    
    protected function ancestorsFetchConditions()
    {
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
        
        $filter_path_begins_with = $this->getState('filter.path_begins_with');
        if ($filter_path_begins_with && is_string($filter_path_begins_with))
        {
            $key = new \MongoRegex('/^'.$filter_path_begins_with.'/i');
            
            $this->setCondition('path', $key);
        }
        
        return $this;
    }

    protected function ancestorsBeforeValidate()
    {
        if (empty($this->slug) && !empty($this->title))
        {
            $this->slug = $this->ancestorsGenerateSlug();
        }
        
        if (empty($this->title)) {
            $this->setError('Title is required');
        }
        
        if ($existing = $this->ancestorsPathExists( $this->path ))
        {
            if ((empty($this->_id) || $this->_id != $existing->_id) && $existing->type == $this->type)
            {
                $this->setError('An item with this title already exists with this parent.');
            }
        }
        
        return parent::beforeValidate();
    }

    /**
     * beforeCreate is triggered before beforeSave,
     * and we ONLY want this to happen if all validations have passed
     */
    protected function ancestorsBeforeSave()
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
        
        $this->path = $this->ancestorsGeneratePath( $this->slug, $this->parent );
        
        if (empty($this->ancestors) && !empty($this->parent))
        {
            $parent_title = null;
            $parent_slug = null;
            $parent_path = null;
            $parent_ancestors = array();
        
            $parent = (new static)->setState('filter.id', $this->parent)->getItem();
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

    protected function ancestorsBeforeUpdate()
    {
        $this->__update_children = isset($this->__update_children) ? $this->__update_children : false;
        
        // if this item's parent is different from it's parent in the database, then we also need to update all the children
        $this->__old = (new static)->load(array('_id' => $this->_id ));
        if ($this->__old->parent != $this->parent || $this->__old->title != $this->title || $this->__old->path != $this->path) 
        {
            // update children after save
            $this->__update_children = true;
        }
                
        return parent::beforeUpdate();
    }

    protected function ancestorsAfterUpdate()
    {
        if ($this->__update_children)
        {
            if ($children = (new static())->setState('filter.parent', $this->_id)->getItems())
            {
                foreach ($children as $child)
                {
                    $child->ancestors = array();
                    $child->path = null;
                    $child->__update_children = true;
                    $child->save();
                }
            }
        }
        
        return $this;
    }
    
    public function ancestorsGeneratePath( $slug, $parent_id=null )
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
    
    public function ancestorsPathExists( $path )
    {
        $item = (new static)->load(array('path'=>$path, 'type'=>$this->__type));
    
        if (!empty($item->_id)) {
            return $item;
        }
    
        return false;
    }

    /**
     *
     * @param string $unique            
     * @return string
     */
    public function ancestorsGenerateSlug($unique = true)
    {
        if (empty($this->title))
        {
            $this->setError('A title is required for generating the slug');
            return $this->checkErrors();
        }
        
        $slug = \Web::instance()->slug($this->title);
        
        if ($unique)
        {
            $base_slug = $slug;
            $n = 1;
            $parent = null;
            if (isset($this->parent) && $this->parent != 'null')
            {
                $parent = $this->parent;
            }
            
            while ($this->ancestorsSlugExists($slug, $parent))
            {
                $slug = $base_slug . '-' . $n;
                $n++;
            }
        }
        
        return $slug;
    }

    /**
     *
     * @param string $slug            
     * @return unknown boolean
     */
    public function ancestorsSlugExists($slug, $parent = null)
    {
        $conditions = array(
            'slug' => $slug
        );
        if (!empty($parent))
        {
            $conditions['parent'] = new \MongoId($parent);
        }
        if (!empty($this->__type))
        {
            $conditions['type'] = $this->__type;
        }
        $clone = (new static())->load($conditions);
        
        if (!empty($clone->id))
        {
            return $clone;
        }
        
        return false;
    }


    /**
     * Determines the depth of this model in the tree
     *
     * @return number
     */
    public function ancestorsGetDepth()
    {
        if (!isset($this->depth))
        {
            $this->depth = substr_count($this->path, "/");
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
            $clone = (new static())->load(array(
                '_id' => new \MongoId((string) $item->parent)
            ));
            unset($item);
            if (!empty($clone->id))
            {
                array_unshift($return, $clone);
                if (!empty($clone->parent))
                {
                    $item = $clone;
                }
            }
        }
        
        return $return;
    }
    
    /**
     * Get the descendants of an item.
     * 
     * @return unknown
     */
    public function ancestorsGetDescendants($exclude_this=true)
    {
        $model = (new static)->setState('filter.path_begins_with', $this->path);
        if ($exclude_this) {
        	$model->setState('filter.ids_excluded', array( $this->id ));
        }        
        $items = $model->getItems();

        return $items;
    }
    
    /**
     * Get a string version of this items title, indented to indicate its depth
     * @return string
     */
    public function ancestorsIndentedTitle($char="&ndash;")
    {
        return str_repeat( $char, substr_count( $this->path, "/" ) - 1 ) . " " . $this->title;
    }
}