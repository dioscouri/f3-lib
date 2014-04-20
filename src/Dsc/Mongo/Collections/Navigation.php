<?php 
namespace Dsc\Mongo\Collections;

class Navigation extends \Dsc\Mongo\Collections\Nested 
{
    protected $__collection_name = 'navigation.items';
    protected $__type = 'navigation.items';
    protected $__config = array(
        'default_sort' => array(
            'lft' => 1
        ),
    );
    
    protected function fetchConditions()
    {
        parent::fetchConditions();
    
        $filter_root = $this->getState('filter.root');
        if (is_bool($filter_root) && $filter_root)
        {
            $this->setCondition('is_root', true);
        } 
        elseif (is_bool($filter_root) && !$filter_root) 
        {
            $this->setCondition('is_root', array( '$ne' => true ) );
        }
    
        $filter_tree = $this->getState('filter.tree');
        if (!empty($filter_tree)) {
            $this->setCondition('tree', new \MongoId((string) $filter_tree ));
        }
    
        $filter_parent = $this->getState('filter.parent');
        if (!empty($filter_parent)) {
            $this->setCondition('parent', new \MongoId((string) $filter_parent ));
        }
    
        $filter_published = $this->getState('filter.published');
        if ($filter_published || (int) $filter_published == 1) {
            // only published items, using both publication dates and published field
            $this->setCondition('published', true);
            
            // TODO When published is changed to publication, enable the following and disable the above            
            //$this->setState('filter.publication_status', 1);
            //$this->setState('filter.published_today', true);
            
        } elseif ((is_bool($filter_published) && !$filter_published) || (strlen($filter_published) && (int) $filter_published == 0)) {
            // only unpublished items
            $this->setCondition('published', array( '$ne' => true ));
            
            // TODO When published is changed to publication, enable the following and disable the above
            //$this->setState('filter.publication_status', 0);
            //$this->setState('filter.published_today', false);            
        }
        
        $filter_published_today = $this->getState('filter.published_today');
        if (strlen($filter_published_today))
        {
            // add $and conditions to the query stack
            if (!$and = $this->getCondition('$and')) {
                $and = array();
            }
        
            $and[] = array('$or' => array(
                array('publication.start.time' => null),
                array('publication.start.time' => array( '$lte' => time() )  )
            ));
        
            $and[] = array('$or' => array(
                array('publication.end.time' => null),
                array('publication.end.time' => array( '$gt' => time() )  )
            ));
        
            $this->setCondition('$and', $and);
        }
        
        $filter_status = $this->getState('filter.publication_status');
        if (strlen($filter_status))
        {
            $this->setCondition('publication.status', $filter_status);
        }        
    
        return $this;
    }
    
    /**
     * Gets all the root level menu items.
     * TODO Move this upstream and make it use __type
     * 
     * @return array
     */
    public static function roots()
    {
        $return = array();
        
        $return = (new static)->emptyState()->setState('filter.root', true)->getItems();
    
        return $return;
    }
}