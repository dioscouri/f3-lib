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
    	
    	$filter_location_within_poly = $this->getState('filter.location_within_poly');

    	//MUST include array of arrays with long, lat;
    	if (count($filter_location_within_poly) > 2)
    	{
    		
    		/*EXAMPLE: 
	    	 * $cords = array(
	   		 *	array('51.12335082548444', '-114.19052124023438'),
	   		 *	array('51.11904092252057', '-114.05593872070312'),
	   		 *	array('51.02325750523972', '-114.02435302734375'),
	   		 *	array('51.01634653617311', '-114.1644287109375'),
	   		 *	);
	       	 *	$item = $model->setState('filter.location_within_poly', $cords)->getItem();
			 *      // found find an address with this cords
			 *      "loc" : {
			 *       "lon" : 51.08282186160978,
			 *       "lat" : -114.10400390625
			 *   },
      		 * 
    		 * 
    		 */
    		
    		//CREATE POLY, and type set values
    		$poly = array();
    		$range = range('a', 'z');
    		for ($i = 0; $i < count($filter_location_within_poly); $i++) {
    			$poly[$range[$i]] = array('x' => (float) $filter_location_within_poly[$i][0], 'y'=>(float) $filter_location_within_poly[$i][1]);
    		}
    		
    		$this->setCondition('loc', array('$within' => array('$polygon'=> $poly)));
    	}
    
    	return $this;
    }
    
    
    public function setLocation($lon, $lat)
    {
     	//TODO ENFORCE lon,lat
    	//ensure floats etc
    	
        $this->set('loc',array('lon' => (float) $lon, 'lat' => (float) $lat));
       
        $this;
    }
    
    public function addIndex() {
    	static::collection()->ensureIndex(array('loc' => '2d' ));
    }

    
}