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
     * Clone an item.  Data from $values takes precedence of data from cloned object.
     *
     * @param unknown_type $mapper
     * @param unknown_type $values
     * @param unknown_type $options
     */
    public function saveAs( $document=array(), $options=array() )
    {	
    	
    	
    	$item_data = $this->cast();
    	// preserve any key=>values from the original item that are not in the new document array
    	$new_values = array_merge( $document, array_diff_key( $item_data, $document ) );
    	unset($new_values[$this->getItemKey()]);
   
    	
    	if ($existing = $this->pathExists( $this->path ))
    	{
    		if($new_values['title'] == $existing->title) {
    			//THEY CHANGED THE TITLE SO lets unset slug and path and regenerate
    			unset($new_values['slug']);
    			unset($new_values['path']);
    		} else {
    			//Set path to something like string/string-2
    			$i = 2;
    			do {
    				$new_values['slug'] = $new_values['slug'] .'-'.$i;
    				$new_values['path'] =  $this->path . '-'.$i;
    				
    				if( $this->pathExists( $new_values['path'] )) {
    					$i++;
    				} else {
    					$i = false;
    				}
				
				} while ($i);
    		}
    		
    		if($new_values['details']['url'] == $existing->{'details.url'}) {
    			//TODO I am not sure if we should append this as well or just alert them, I assume they would clone it to make a simliar URL so adding text to it could be annoying
    			\Dsc\System::instance()->addMessage('Item shares a URL with another menu Item be sure to change it', 'warning');
    			 
    		}
    	}
    	
    	
    	
    	$item = new static( $new_values );
    
    	return $item->insert(array(), $options);
    }
    
    /**
     *
     * @return array
     */
    public function getRoots()
    {
        $return = array();
        $return = $this->emptyState()->setState('filter.root', true)->getList();
    
        return $return;
    }
}