<?php
namespace Dsc\Traits\Controllers;

/**
 * Handle redirects after saves/creations, system messages, etc
 */
trait CrudItem
{
    use \Dsc\Traits\Controllers\Crud;
    
    /**
     * These MUST be defined in your controller.
     * Here is a typical format.
     * 
    protected $list_route = '/admin/items';
    protected $create_item_route = '/admin/item';
    protected $get_item_route = '/admin/item/{id}';    
    protected $edit_item_route = '/admin/item/{id}/edit';
    */
        
    abstract protected function getModel();
    abstract protected function getItem();

    abstract protected function displayCreate();
    abstract protected function displayRead();
    abstract protected function displayEdit();

    protected function getItemKey()
    {
        if (empty($this->crud_item_key)) {
            $this->crud_item_key = $this->getModel()->getItemKey();
        }
        
        if (empty($this->crud_item_key)) {
            throw new \Exception('Must define an item key');
        }
        
        return $this->crud_item_key;
    }
    
    protected function doCreate(array $data)
    {
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $f3->set('flash', $flash );
        
        $use_flash = \Dsc\System::instance()->getUserState('use_flash.' . $this->create_item_route);
        if (!$use_flash) {
            $flash->store(array());
        }
        \Dsc\System::instance()->setUserState('use_flash.' . $this->create_item_route, false);
        
        $model = $this->getModel();
        $f3->set('model', $model );
        $this->displayCreate();
        
        return $this;
    }
    
    protected function doEdit(array $data)
    {
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $f3->set('flash', $flash );
    
        $model = $this->getModel();
        $item = $this->getItem();
    
        $f3->set('model', $model );
        $f3->set('item', $item );
    
        if (method_exists($item, 'cast')) {
            $item_data = $item->cast();
        } else {
            $item_data = \Joomla\Utilities\ArrayHelper::fromObject($item);
        }
        $flash->store($item_data);
    
        $this->displayEdit();
    
        return $this;
    }
    
    protected function doRead(array $data, $key=null) {
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $f3->set('flash', $flash );
    
        $model = $this->getModel();
        $item = $this->getItem();
    
        $f3->set('model', $model );
        $f3->set('item', $item );
    
        if (method_exists($item, 'cast')) {
            $item_data = $item->cast();
        } else {
            $item_data = \Joomla\Utilities\ArrayHelper::fromObject($item);
        }
        $flash->store($item_data);
    
        $this->displayRead();
    
        return $this;
    }
    
    protected function doAdd($data) 
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
                
        if (empty($this->create_item_route)) {
            throw new \Exception('Must define a route for creating the item');
        }
                
        if (empty($this->edit_item_route)) {
            throw new \Exception('Must define a route for editing the item'); 
        }
        
        if (!isset($data['submitType'])) {
            $data['submitType'] = "save_edit";
        }
        
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $model = $this->getModel();
        
        // save
        try {
            $values = $data;
            unset($values['submitType']);
            //\Dsc\System::instance()->addMessage(\Dsc\Debug::dump($values), 'warning');
            $this->item = $model->create($values);
        }
        catch (\Exception $e) {
            \Dsc\System::instance()->addMessage('Save failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            foreach ($model->getErrors() as $error)
            {
                \Dsc\System::instance()->addMessage($error, 'error');
            }
            
            if ($f3->get('AJAX')) {
                // output system messages in response object
                return $this->outputJson( $this->getJsonResponse( array(
                        'error' => true,
                        'message' => \Dsc\System::instance()->renderMessages()
                ) ) );
            }
            
            // redirect back to the create form with the fields pre-populated
            \Dsc\System::instance()->setUserState('use_flash.' . $this->create_item_route, true);
            $flash->store($data);
            
            $this->setRedirect( $this->create_item_route );
                        
            return false;
        }
                
        // redirect to the editing form for the new item
        \Dsc\System::instance()->addMessage('Item saved');
        
        if (method_exists($this->item, 'cast')) {
            $this->item_data = $this->item->cast();
        } else {
            $this->item_data = \Joomla\Utilities\ArrayHelper::fromObject($this->item);
        }
        
        if ($f3->get('AJAX')) {
            return $this->outputJson( $this->getJsonResponse( array(
                    'message' => \Dsc\System::instance()->renderMessages(),
                    'result' => $this->item_data
            ) ) );
        }
        
        switch ($data['submitType']) 
        {
            case "save_new":
                $route = $this->create_item_route;
                break;
            case "save_close":
                $route = $this->list_route;
                break;
            default:
                $flash->store($this->item_data);
                $id = $this->item->get( $this->getItemKey() );
                $route = str_replace('{id}', $id, $this->edit_item_route );                
                break;
        }

        $this->setRedirect( $route );
        
        return $this;
    }
    
    protected function doUpdate(array $data, $key=null) 
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
                
        if (empty($this->create_item_route)) {
            throw new \Exception('Must define a route for creating the item');
        }
                
        if (empty($this->edit_item_route)) {
            throw new \Exception('Must define a route for editing the item'); 
        }
        
        if (!isset($data['submitType'])) {
            $data['submitType'] = "save_edit";
        }
        
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $model = $this->getModel();
        $this->item = $this->getItem();
        
        // save
        $save_as = false;
        try {
            $values = $data; 
            unset($values['submitType']);
            //\Dsc\System::instance()->addMessage(\Dsc\Debug::dump($values), 'warning');
            if ($data['submitType'] == 'save_as') 
            {
                $this->item = $model->saveAs($this->item, $values);
                \Dsc\System::instance()->addMessage('Item cloned. You are now editing the new item.');
            } 
            else 
            {
                $this->item = $model->update($this->item, $values);
                \Dsc\System::instance()->addMessage('Item updated');
            }
            
        }
        catch (\Exception $e) {
            \Dsc\System::instance()->addMessage('Save failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            foreach ($model->getErrors() as $error)
            {
                \Dsc\System::instance()->addMessage($error, 'error');
            }
            
            if ($f3->get('AJAX')) {
                // output system messages in response object
                return $this->outputJson( $this->getJsonResponse( array(
                        'error' => true,
                        'message' => \Dsc\System::instance()->renderMessages()
                ) ) );
            }
        
            // redirect back to the create form with the fields pre-populated
            $flash->store($data);
            $id = $this->item->get( $this->getItemKey() );
            $route = str_replace('{id}', $id, $this->edit_item_route );
            
            $this->setRedirect( $route );
            
            return false;           
        }
        
        // redirect to the editing form for the new item
        if (method_exists($this->item, 'cast')) {
            $this->item_data = $this->item->cast();
        } else {
            $this->item_data = \Joomla\Utilities\ArrayHelper::fromObject($this->item);
        }
        
        if ($f3->get('AJAX')) {
            return $this->outputJson( $this->getJsonResponse( array(
                    'message' => \Dsc\System::instance()->renderMessages(),
                    'result' => $this->item_data
            ) ) );
        }
        
        switch ($data['submitType'])
        {
            case "save_new":
                $route = $this->create_item_route;
                break;
            case "save_close":
                $route = $this->list_route;
                break;
            case "save_as":
            default:
                $flash->store($this->item_data);
                $id = $this->item->get( $this->getItemKey() );
                $route = str_replace('{id}', $id, $this->edit_item_route );
                break;
        }

        $this->setRedirect( $route );
        
        return $this;        
    }
        
    protected function doDelete(array $data, $key=null) 
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
        
        $f3 = \Base::instance();
        $model = $this->getModel();
        $this->item = $this->getItem();
        
        try {
            $model->delete( $this->item );
            \Dsc\System::instance()->addMessage('Item deleted');
        }
        catch (\Exception $e) {
            \Dsc\System::instance()->addMessage('Delete failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            foreach ($model->getErrors() as $error)
            {
                \Dsc\System::instance()->addMessage($error, 'error');
            }
        
            if ($f3->get('AJAX')) {
                // output system messages in response object
                return $this->outputJson( $this->getJsonResponse( array(
                        'error' => true,
                        'message' => \Dsc\System::instance()->renderMessages()
                ) ) );
            }
        
            // redirect back to the list view
            $this->setRedirect( $this->list_route );
            
            return false;
        }
        
        if ($f3->get('AJAX')) {
            return $this->outputJson( $this->getJsonResponse( array(
                    'message' => \Dsc\System::instance()->renderMessages()
            ) ) );
        }

        $this->setRedirect( $this->list_route );
        
        return $this;
    }

}