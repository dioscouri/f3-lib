<?php
namespace Dsc\Traits\Controllers;
 
trait AdminList 
{
    /**
     * These MUST be defined in your controller.
     * Here is a typical format.
     *
     protected $list_route = '/admin/items';
     */
    
    abstract protected function getModel();
    
    /**
     * Delete a list of records
     */
    public function delete()
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
        
        $f3 = \Base::instance();
        $data = $f3->get('REQUEST');
        
        $selected = array();
        if (!empty($data['ids'])) 
        {
            $input = (array) $data['ids'];
            foreach ($input as $id)
            {
                if ($id = $this->inputfilter->clean( $id, 'alnum' )) {
                    $selected[] = $id;
                }
            }
            
            if (!empty($selected)) 
            {
                $model = $this->getModel();
                if ($items = $model->setState('filter.ids', $selected)->getList())
                {
                    foreach ($items as $item)
                    {
                        if ($this->canDelete($item)) {
                            try {
                                $model->delete($item);
                            } catch (\Exception $e) {
                                $this->setError(true);
                                \Dsc\System::instance()->addMessage('Delete failed with the following errors:', 'error');
                                foreach ($model->getErrors() as $error)
                                {
                                    \Dsc\System::instance()->addMessage($error, 'error');
                                }
                            }
                        } else {
                            $this->setError(true);
                            \Dsc\System::instance()->addMessage('Not allowed to delete this record.', 'error');                
                        }
                    }
                    
                    if (!$errors = $this->getErrors()) 
                    {
                        \Dsc\System::instance()->addMessage('Items deleted');
                    }
                }                
            } 
            else 
            {
                \Dsc\System::instance()->addMessage('No items selected to delete.', 'warning');
            }
        }
        else
        {
            \Dsc\System::instance()->addMessage('No items selected to delete.', 'warning');
        }        

        $f3->reroute( $this->list_route );
         
        return;
    }
    
    protected function canDelete($item)
    {
        return true;
    }
    
    public function reorder()
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
        
        $f3 = \Base::instance();
        $data = $f3->get('POST');
        
        $selected = array();
        if (!empty($data['ids']) && !empty($data['ordering']))
        {
            $input = (array) $data['ids'];
            $ordering = (array) $data['ordering'];
            foreach ($input as $id)
            {
                if ($id = $this->inputfilter->clean( $id, 'alnum' )) {
                    if (isset($ordering[$id])) {
                        $selected[] = $id;
                    }
                }
            }
        
            if (!empty($selected))
            {
                $model = $this->getModel();
                if ($items = $model->setState('filter.ids', $selected)->getList())
                {
                    $options = array();
                    
                    foreach ($items as $item)
                    {
                        if ($this->canUpdate($item)) {
                            try {
                                $id = (string) $item->id; 
                                $model->getCollection()->update(
                                    array('_id'=>$item->_id),
                                    array( '$set' => array( 'ordering' => $ordering[$id] ) )
                                );
                                if ($item->tree) {
                                    if (empty($options['trees'])) {
                                        $options['trees'] = array();
                                    }
                                    if (!in_array($item->tree, $options['trees'])) {
                                        $options['trees'][] = $item->tree;
                                    }                                    
                                }
                            } catch (\Exception $e) {
                                $this->setError(true);
                                \Dsc\System::instance()->addMessage('Reorder failed with the following errors:', 'error');
                                foreach ($model->getErrors() as $error)
                                {
                                    \Dsc\System::instance()->addMessage($error, 'error');
                                }
                            }
                        } else {
                            $this->setError(true);
                            \Dsc\System::instance()->addMessage('Not allowed to reorder this record.', 'error');
                        }
                    }
        
                    if (!$errors = $this->getErrors())
                    {
                        if (method_exists($model, 'reorder')) 
                        {
                            $model->reorder($options);
                        }
                        
                        \Dsc\System::instance()->addMessage('Items reordered');
                    }
                }
            }
            else
            {
                \Dsc\System::instance()->addMessage('No items selected to reorder.', 'warning');
            }
        }
        else
        {
            \Dsc\System::instance()->addMessage('No items selected to reorder.', 'warning');
        }
        
        $f3->reroute( $this->list_route );
         
        return;
    }
    
    protected function canUpdate($item)
    {
        return true;
    }
}
?>