<?php
namespace Dsc\Traits\Models;

trait Locatable
{

    public $loc = array(
        'lon' => null,
        'lat' => null
    );
	
    protected function locatableFetchConditions()
    {
    	/* array(lon,lat);*/
    	$filter_location_near = $this->getState('filter.location_near');
    	
  
    	if (count($filter_location_near) == 2)
    	{
    		$this->setCondition('loc', array('$near' => array($filter_location_near[0], $filter_location_near[1])));
    	}
    
    	return $this;
    }
    
    
    public function setLocation(array $location)
    {
     	//TODO ENFORCE lon,lat
    	//ensure floats etc
    	
    	
        $this->set('loc',array((float) $location[0], (float) $location[1]));
       
        $this;
    }
    
    public function addIndex() {
    	static::collection()->ensureIndex(array('loc' => '2d' ));
    }

    
}