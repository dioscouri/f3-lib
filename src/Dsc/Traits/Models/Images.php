<?php
namespace Dsc\Traits\Models;

trait Images
{
    /*
     * Adds these to the document
     */
    public $featured_image = array();
    public $images = array();
    
    protected function imagesFetchConditions()
    {
        
        $filter_hasimages = $this->getState('filter.hasimages');
        if ($filter_hasimages)
        {
            $this->setCondition('title', $filter_title);
        }
        
        $filter_hasfeaturedimage = $this->getState('filter.hasfeaturedimage');
        if ($filter_hasfeaturedimage)
        {
        	$this->setCondition('title', $filter_title);
        }
    
        return $this;
    }
    
    protected function imagesBeforeValidate()
    {
     	if (!empty($this->images))
        {
            $images = array();
            $current = $this->images;
            $this->images = array();
            
            foreach ($current as $image)
            {
                if (!empty($image['image']))
                {
                    $images[] = array(
                        'image' => $image['image']
                    );
                }
            }
            
            $this->images = $images;
        }
    
        return parent::beforeValidate();
    }
     
    
    /**
     * Get all the images associated with a product
     * incl.
     * featured image, related images, etc
     *
     * @param unknown $cast
     * @return array
     */
    public function images()
    {
    	$featured_image = array();
    	if (!empty($this->featured_image['slug']))
    	{
    		$featured_image = array(
    				$this->featured_image['slug']
    		);
    	}
    
    	$related_images = \Dsc\ArrayHelper::where($this->images, function ($key, $ri)
    	{
    		if (!empty($ri['image']))
    		{
    			return $ri['image'];
    		}
    	});
    
    	$images = array_unique(array_merge(array(), (array) $featured_image, (array) $related_images));
    
    	return $images;
    }
    
    
}