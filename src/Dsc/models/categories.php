<?php 
namespace Dsc\Models;

class Categories extends \Dsc\Models\Db\Mongo 
{
    protected $collection = 'common.categories';
    protected $type = 'common.categories';
    protected $default_ordering_direction = '1';
    protected $default_ordering_field = 'path';
    
    /*
    protected $design = array(
            '_id' => MongoId(),
            'title' => string INDEX
            'description' => text
            'slug' => string INDEX
            'parent' => MongoId(),
            'path' => string => '/path/to/the/current/category/using/slugs' INDEX UNIQUE
            'ancestors' => array( array('MongoId', 'slug', 'title') )
            );
    */
    
    public function __construct($config=array())
    {
        $config['filter_fields'] = array(
            'title', 'path'
        );
        $config['order_directions'] = array('1', '-1');
        
        parent::__construct($config);
    }
    
    protected function fetchFilters()
    {
        $this->filters = array();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('title'=>$key);
            $where[] = array('slug'=>$key);
            $where[] = array('path'=>$key);
            $where[] = array('description'=>$key);
    
            $this->filters['$or'] = $where;
        }
    
        $filter_id = $this->getState('filter.id');
        if (strlen($filter_id))
        {
            $this->filters['_id'] = new \MongoId((string) $filter_id);
        }
        
        $filter_parent = $this->getState('filter.parent');
        if (strlen($filter_parent))
        {
            $this->filters['parent'] = (string) $filter_parent;
        }
        
        $filter_ids = $this->getState('filter.ids');
        if (!empty($filter_ids) && is_array($filter_ids))
        {
            $ids = array();
            foreach ($filter_ids as $filter_id) {
                $ids[] = new \MongoId((string) $filter_id);
            }
            $this->filters['_id'] = array(
                '$in' => $ids
            );
        }
        
        $filter_type = $this->getState('filter.type');
        if ($filter_type) {
            if (is_bool($filter_type) && $filter_type) {
                $this->filters['type'] = $this->type;
            } elseif (strlen($filter_type)) {
                $this->filters['type'] = $filter_type;
            }
        }
    
        return $this->filters;
    }
    
    public function validate( $values, $options=array(), $mapper=null )
    {
        if (empty($values['title'])) {
            $this->setError('Title is required');
        }
        
        if (empty($values['slug'])) 
        {
            $values['slug'] = \Joomla\Filter\OutputFilter::stringURLUnicodeSlug($values['title']);
        }
        
        if (!isset($values['parent']) || $values['parent'] == "null")
        {
            $values['parent'] = null;
        }
        
        if (empty($values['path']))
        {
            $values['path'] = $this->generatePath( $values['slug'], $values['parent'] );
        }        
        
        // is the path unique?
        if ($existing = $this->pathExists( $values['path'])) 
        {
            if (empty($mapper->_id) || $existing->_id != $mapper->_id) 
            {
                $this->setError('A category with this title already exists with this parent.');
            }            
        }

        return parent::validate( $values, $options );
    }
    
    public function save( $values, $options=array(), $mapper=null )
    {
        if (empty($options['skip_validation'])) 
        {
            $this->validate( $values, $options, $mapper );
        }
        
        if (empty($values['type'])) {
            $values['type'] = $this->type;
        }
        
        /*
         protected $design = array(
                 '_id' => MongoId(),
                 'title' => string INDEX
                 'description' => text
                 'slug' => string INDEX
                 'parent' => MongoId(),
                 'path' => string => '/path/to/the/current/category/using/slugs' INDEX UNIQUE
                 'ancestors' => array( array('MongoId', 'slug', 'title') )
         );
        */
        
        // if no slug exists, generate it and make sure it's unique
        if (empty($values['slug'])) 
        {
            $values['slug'] = \Joomla\Filter\OutputFilter::stringURLUnicodeSlug($values['title']);
        }
        
        // if no parent is set, set it
        if (!isset($values['parent']) || $values['parent'] == "null")
        {
            $values['parent'] = null;
        }
        
        $parent_title = null;
        $parent_slug = null;
        $parent_path = null;
        $parent_ancestors = array();
        
        // get the parent's details if it exists
        if (!empty($values['parent'])) 
        {
            $model = Categories::instance()->setState('filter.id', $values['parent']);
            $parent = $model->getItem();
            
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
            $model->emptyState();
            $parent->reset();
        }
        
        // if no path exists, set it
        if (empty($values['path'])) 
        {
            $values['path'] = $parent_path . "/" . $values['slug'];
        }
        
        if (!isset($values['ancestors']) && !empty($values['parent'])) 
        {
            $values['ancestors'] = $parent_ancestors;
            $values['ancestors'][] = array(
                'id' => $values['parent'],
                'slug' => $parent_slug,
                'title' => $parent_title
            );
        }
        
        $options['skip_validation'] = true; // we've already done it above, so stop the parent from doing it
        
        return parent::save( $values, $options, $mapper );
    }
    
    public function update( $mapper, $values, $options=array() )
    {
        $update_children = false;
        // if the mapper's parent is different from the $values['parent'], then we also need to update all the children
        if ($mapper->parent != @$values['parent']) {
            // update children after save
            $update_children = true;
        }

        if ($updated = $this->save( $values, $options, $mapper )) 
        {
            if ($update_children) 
            {
                if ($children = $this->emptyState()->setState('filter.parent', $updated->_id)->getList()) 
                {
                    foreach ($children as $child) 
                    {
                        $child_values = $child->cast();
                        unset($child_values['ancestors']);
                        unset($child_values['path']);
                        $this->update( $child, $child_values );
                    }
                }
            }
        }
        
        return $updated;
    }
    
    public function generatePath( $slug, $parent_id=null )
    {
        $path = null;
        
        if (empty($parent_id)) {
            return "/" . $slug;
        }
        
        // get the parent's path, append the slug
        $model = Categories::instance()->setState('filter.id', $parent_id);
        $mapper = $model->getItem();
        $model->emptyState();
        
        if (!empty($mapper->path)) {
            $path = $mapper->path;
        }
        
        $path .= "/" . $slug;
        
        return $path; 
    }
    
    public function pathExists( $path )
    {
        $mapper = $this->getMapper();
        $mapper->load(array('path'=>$path));
        
        if (!empty($mapper->_id)) {
            return $mapper;
        }
        
        return false;
    }

}