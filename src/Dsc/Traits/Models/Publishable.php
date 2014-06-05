<?php
namespace Dsc\Traits\Models;

trait Publishable
{

    public $publication = array(
        'status' => 'published',
        'start_date' => null,
        'start_time' => null,
        'end_date' => null,
        'end_time' => null,
        'start' => null,
        'end' => null
    );

    /**
     * Checks if the model is published as of today
     *
     * @param string $check_status            
     * @param string $status_value            
     * @return boolean
     */
    public function published($check_status = true, $status_value = 'published')
    {
        $return = false;
        
        if ($check_status && $this->{'publication.status'} != $status_value)
        {
            $return = false;
        }
        
        if ((empty($this->{'publication.start.time'}) || $this->{'publication.start.time'} <= time()) && (empty($this->{'publication.end.time'}) || $this->{'publication.end.time'} >= time()))
        {
            if (!$check_status)
            {
                $return = true;
            }
            
            elseif ($check_status && $this->{'publication.status'} == $status_value)
            {
                $return = true;
            }
            
            elseif ($check_status && $this->{'publication.status'} != $status_value)
            {
                $return = false;
            }
        }
        
        return $return;
    }

    /**
     * Changes the publication.status
     *
     * @param string $status_value            
     */
    public function publish($status_value = 'published')
    {
        return $this->update(array(
            'publication.status' => $status_value
        ), array(
            'overwrite' => false
        ));
    }

    /**
     * Changes the publication.status
     *
     * @param string $status_value            
     */
    public function unpublish($status_value = 'unpublished')
    {
        return $this->update(array(
            'publication.status' => $status_value
        ), array(
            'overwrite' => false
        ));
    }

    /**
     * Method to check that publication fields are set to some defaults.
     * You will need to call this in your model's beforeSave() method.
     *
     * @return \Dsc\Traits\Models\Publishable
     */
    protected function publishableBeforeSave()
    {
        if (!empty($this->{'publication.start_date'}))
        {
            $string = $this->{'publication.start_date'};
            if (!empty($this->{'publication.start_time'}))
            {
                $string .= ' ' . $this->{'publication.start_time'};
            }
            $this->{'publication.start'} = \Dsc\Mongo\Metastamp::getDate(trim($string));
        }
        else
        {
            $this->{'publication.start'} = \Dsc\Mongo\Metastamp::getDate('now');
        }
        
        if (empty($this->{'publication.end_date'}))
        {
            unset($this->{'publication.end'});
        }
        elseif (!empty($this->{'publication.end_date'}))
        {
            $string = $this->{'publication.end_date'};
            if (!empty($this->{'publication.end_time'}))
            {
                $string .= ' ' . $this->{'publication.end_time'};
            }
            $this->{'publication.end'} = \Dsc\Mongo\Metastamp::getDate(trim($string));
        }
        
        return $this;
    }
    
    /**
     * Get a status label class 
     * 
     * @return string
     */
    public function publishableStatusLabel()
    {
        switch ($this->{'publication.status'}) 
        {
        	case "unpublished":
        	    $label_class = 'label-danger';
        	    break;
    	    case "pending":
    	        $label_class = 'label-info';
    	        break;        	    
        	case "draft":
        	    $label_class = 'label-default';
        	    break;
        	case "published":
        	default:
        	    $label_class = 'label-success';
        	    
        	    if (!$this->published()) 
        	    {
        	        // would mean status == published but publication date range doesn't include today
        	        $label_class = 'label-warning';
        	    }
        	    break;        
        }        
        
        return $label_class;
    }
}