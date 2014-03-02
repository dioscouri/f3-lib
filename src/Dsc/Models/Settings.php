<?php 
namespace Dsc\Models;

abstract class Settings extends \Dsc\Models\Db\Mongo 
{
    protected $collection = 'common.settings';
    protected $type = 'common.settings';
    protected $default_ordering_direction = '1';
    protected $default_ordering_field = 'metadata.created.time';
    
    public function __construct($config=array())
    {
        $config['filter_fields'] = array(
                'type', 'metadata.created.time', 'metadata.last_modified.time'
        );
        $config['order_directions'] = array('1', '-1');
    
        parent::__construct($config);
    }
    
    protected function fetchFilters()
    {
        $this->filters = array();
    
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
    
    public function save( $values, $options=array(), $mapper=null )
    {
        if (empty($values['type'])) {
            if (!empty($mapper->{'type'})) {
                $values['type'] = $mapper->{'type'};
            }            
            else {
                $values['type'] = $this->type;
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
    
    public function populateState(){
    	return parent::populateState()->setState( 'filter.type', $this->type);
    }
}