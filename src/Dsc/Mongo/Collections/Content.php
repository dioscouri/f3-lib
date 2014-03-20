<?php 
namespace Dsc\Mongo\Collections;

class Content extends \Dsc\Mongo\Collections\Describable 
{
    /**
     * Default Document Structure
     * @var unknown
     */
    public $copy; // text
    public $publication = array(
    	'status' => 'published',
        'start_date' => null,
        'start_time' => null,
        'end_date' => null,
        'end_time' => null,
        'start' => null,
        'end' => null
    );
    
    protected $__collection_name = 'common.content';
    protected $__type = 'common.content';
    protected $__config = array(
        'default_sort' => array(
            'metadata.created.time' => 1
        ),
    );
    
    protected function fetchConditions()
    {
        parent::fetchConditions();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            
            $regex = '/^[0-9a-z]{24}$/';
            if (preg_match($regex, (string) $filter_keyword))
            {
                $where[] = array('_id'=>new \MongoId((string) $filter_keyword));
            }
            $where[] = array('slug'=>$key);
            $where[] = array('title'=>$key);
            $where[] = array('copy'=>$key);
            $where[] = array('description'=>$key);
            $where[] = array('metadata.creator.name'=>$key);

            $this->setCondition('$or', $where);
        }
        
        $filter_copy_contains = $this->getState('filter.copy-contains');
        if (strlen($filter_copy_contains))
        {
            $key =  new \MongoRegex('/'. $filter_copy_contains .'/i');
            $this->setCondition('copy', $key);
        }
        
        // TODO Add conditions for publication date range and status
        
        return $this;
    }
    
    public function validate()
    {
        if (empty($this->title)) {
            $this->setError('Title is required');
        }
        
        if (empty($this->slug)) {
            $this->setError('A slug is required');
        }
        
        if ($existing = $this->slugExists( $this->slug ))
        {
            if (empty($this->id) || $existing->id != $this->id)
            {
                $this->setError('An item with this slug already exists.  Slugs must be unique.');
            }
        }
        
        return parent::validate();
    }
    
    protected function beforeSave()
    {
        if (empty($this->{'publication.start'})) {
            $this->{'publication.start'} = \Dsc\Mongo\Metastamp::getDate( $this->{'publication.start_date'} . ' ' . $this->{'publication.start_time'} );
        }
        
        if (empty($this->{'publication.end'}) && !empty($this->{'publication.end_date'})) {
            $string = $this->{'publication.end_date'};
            if (!empty($this->{'publication.end_time'})) {
                $string .= ' ' . $this->{'publication.end_time'};
            }
            $this->{'publication.end'} = \Dsc\Mongo\Metastamp::getDate( trim( $string ) );
        }        
        
        return parent::beforeSave();
    }
}