<?php 
namespace Dsc\Models;

abstract class Nodes extends \Dsc\Models\Db\Mongo 
{
    protected $collection = 'common.content';
    protected $type = 'common.content';
    protected $default_ordering_direction = '1';
    protected $default_ordering_field = 'metadata.created.time';
    
    public function __construct($config=array())
    {
        $config['filter_fields'] = array(
                'metadata.title', 'metadata.creator.name', 'metadata.created.time', 'metadata.last_modified.time'
        );
        $config['order_directions'] = array('1', '-1');
    
        parent::__construct($config);
    }
    
    /**
     * // TODO Add ability to get distinct tags within types
     *
     * @param array $types
     * @return unknown
     */
    public function getTags($types=array())
    {
        $collection = $this->getCollection();
    
        // TODO if $types, only get tags used by items of those types
        $tags = $collection->distinct("metadata.tags");
        $tags = array_values( array_filter( $tags ) );
    
        return $tags;
    }
    
    public function save( $values, $options=array(), $mapper=null )
    {
        if (empty($values['metadata']['creator'])) {
            if (!empty($mapper->{'metadata.creator'})) {
                $values['metadata']['creator'] = $mapper->{'metadata.creator'};
            }
            elseif ($user = \Base::instance()->get('SESSION.admin.user')) {
                $values['metadata']['creator'] = array(
                        'id' => $user->id,
                        'name' => $user->name
                );
            }
            elseif ($user = \Base::instance()->get('SESSION.user')) {
                $values['metadata']['creator'] = array(
                        'id' => $user->id,
                        'name' => $user->name
                );
            }            
        }
        
        if (empty($values['metadata']['type'])) {
            if (!empty($mapper->{'metadata.type'})) {
                $values['metadata']['type'] = $mapper->{'metadata.type'};
            }            
            else {
                $values['metadata']['type'] = $this->type;
            }
        }
        
        if (empty($values['metadata']['created'])) {
            if (!empty($mapper->{'metadata.created'})) {
                $values['metadata']['created'] = $mapper->{'metadata.created'};
            }
            else {
                $values['metadata']['created'] = \Dsc\Mongo\Metastamp::getDate('now');
            }
        }
        
        $values['metadata']['last_modified'] = \Dsc\Mongo\Metastamp::getDate('now');
                
        if (empty($options['skip_validation']))
        {
            $this->validate( $values, $options, $mapper );
        }
        
        return parent::save( $values, $options, $mapper );
    }
    
    /**
     * // TODO Add ability to check for distinct slugs within types
     *
     * @param string $slug
     * @return unknown|boolean
     */
    public function slugExists( $slug )
    {
        $mapper = $this->getMapper();
        $mapper->load(array('metadata.slug'=>$slug));
    
        if ($mapper->id) {
            return $mapper;
        }
    
        return false;
    }
}