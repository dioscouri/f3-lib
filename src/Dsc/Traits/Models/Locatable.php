<?php
namespace Dsc\Traits\Models;

trait Locatable
{

    public $loc = array(
        'lon' => null,
        'lat' => null
    );
    
    public $__locate_from_address = false;
    
	
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
			 *      // found find an address with these cords
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
    
        $this->set('loc', array('lon' =>(float) $lon, 'lat' => (float) $lat));
  
      return  $this;
    }
    
    /*
     * TODO if an item has an address, but no cords get them from an API. 
     * 
     * */
    public function geocodeAddress( $address = null) {
    	if(empty($address) && empty($this->{'loc.address'}))  {
    		return;
    	}
    	
    	if(!empty($address)) {
    		$this->set('loc.address', $address);
    	}
    	//TODO google geocode api
    		
    }
    
    //getting the documents closest to a cord set, and returns distance in miles
    public function findNearDistanceMiles ($lon, $lat, $count = 20) {
    	$docs = (new static)->getDB()->command(
    			array( 'geoNear' =>  $this->__collection_name,
    					'near' => array($lon, $lat),
    					'spherical' => true,
    					'distanceMultiplier'=> 3959,
    					'limit' =>     $count			)
    	);
    	
    	return $docs;
    }
    
    //GEO searching straight fails without index so  here is a method for that.
    public function addIndex() {
    	//also 2dsphere
    	static::collection()->ensureIndex(array('loc' => '2d' ));
    }
    
   
    protected function locatableBeforeValidate()
    {
    	//IF loc is empty
    	if (empty($this->{'loc.lon'}) || empty($this->{'loc.lat'}))
    	{
    		//location is empty lets check for $loc and $lng vars in object
    		if(!empty($this->loc) && !empty($this->lat)) {
    			$this->setLocation($this->lon, $this->lat );
    		} elseif(!empty($this->{'loc.address'}) && $this->__locate_from_address){
    			//if there is no cords, but an address lets get the cords
    			$this->geocodeAddress();
    		} 
    	}
    	
    	return parent::beforeValidate();
    }
    
    
    
    public function locatableValidate()
    {
    	if (empty($this->{'loc.lon'})) {
    		$this->setError('Longitude is  is required, as loc.lon');
    	}
    	if (empty($this->{'loc.lat'})) {
    		$this->setError('Latitude is  is required, as loc.lat');
    	}
    
    	return parent::validate();
    }

    
}