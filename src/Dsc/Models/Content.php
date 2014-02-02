<?php 
namespace Dsc\Models;

class Content extends Nodes 
{
    protected $collection = 'common.content';
    protected $type = 'common.content';
    protected $default_ordering_direction = '1';
    protected $default_ordering_field = 'metadata.created.time';
    
    public function __construct($config=array())
    {
        parent::__construct($config);
        
        $this->filter_fields = array_merge( $this->filter_fields, array(
            'details.copy'
        ) );
    }
    
    protected function fetchFilters()
    {
        $this->filters = array();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('metadata.title'=>$key);
            $where[] = array('details.copy'=>$key);
            $where[] = array('metadata.creator.name'=>$key);
    
            $this->filters['$or'] = $where;
        }
    
        $filter_id = $this->getState('filter.id');
        if (strlen($filter_id))
        {
            $this->filters['_id'] = new \MongoId((string) $filter_id);
        }
        
        $filter_copy_contains = $this->getState('filter.copy-contains');
        if (strlen($filter_copy_contains))
        {
            $key =  new \MongoRegex('/'. $filter_copy_contains .'/i');
            $this->filters['details.copy'] = $key;
        }
        
        $filter_type = $this->getState('filter.type');
        if ($filter_type) {
            if (is_bool($filter_type) && $filter_type) {
                $this->filters['metadata.type'] = $this->type;
            } elseif (strlen($filter_type)) {
                $this->filters['metadata.type'] = $filter_type;
            }
        }
    
        return $this->filters;
    }
    
    public function validate( $values, $options=array(), $mapper=null )
    {
        if (empty($values['metadata']['title']) && empty($mapper->{'metadata.title'})) {
            $this->setError('Title is required');
        }
        
        // if no slug exists, generate it
        if (empty($values['metadata']['slug']) && !empty($values['metadata']['title']))
        {
            $values['metadata']['slug'] = $this->generateSlug( $values, $mapper, false );
        }

        if (empty($values['metadata']['slug'])) {
            $this->setError('A slug is required');
        }
        
        // is the slug unique?
        if ($existing = $this->slugExists( $values['metadata']['slug'] ))
        {
            if (empty($mapper->id) || $existing->id != $mapper->id)
            {
                $this->setError('An entry with this slug already exists.  Slugs must be unique.');
            }
        }
    
        return $this->checkErrors();
    }
    
    public function save( $values, $options=array(), $mapper=null )
    {
        // if no slug exists, generate it and make sure it's unique
        if (empty($values['metadata']['slug']))
        {
            $values['metadata']['slug'] = $this->generateSlug( $values, $mapper );
        }
        
        if (!empty($values['metadata']['tags']) && !is_array($values['metadata']['tags']))
        {
            $values['metadata']['tags'] = trim($values['metadata']['tags']);
            if (!empty($values['metadata']['tags'])) {
                $values['metadata']['tags'] = \Base::instance()->split( (string) $values['metadata']['tags'] );
            }
        }
        
        if (empty($values['metadata']['tags'])) {
            unset($values['metadata']['tags']);
        }

        // create an array of categories from the category_ids, if present
        if (isset($values['category_ids'])) 
        {
            $category_ids = $values['category_ids'];
            unset($values['category_ids']);
            
            $categories = array();
            $model = new \Dsc\Models\Categories;
            if ($list = $model->setState('select.fields', array('title'))->setState('filter.ids', $category_ids)->getList()) {
                foreach ($list as $list_item) {
                    $cast = $list_item->cast();
                    $cat = array(
                        'id' => (string) $cast['_id'],
                        'title' => $cast['title']
                    );
                    unset($cast);
                    $categories[] = $cat;
                }
            }
            $values['metadata']['categories'] = $categories; 
        }
    
        return parent::save( $values, $options, $mapper );
    }
    
    public function generateSlug( $values, $mapper=null, $unique=true )
    {
        if (empty($values['metadata']['title'])) {
            $this->setError('A title is required for generating the slug');
        }
        $this->checkErrors();

        $slug = \Web::instance()->slug( $values['metadata']['title'] );
        
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
}