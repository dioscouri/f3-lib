<?php
namespace Dsc\Traits\Controllers;

/**
 * Handle redirects after saves/creations, system messages, etc
 */
trait CrudItemCollection
{
    use \Dsc\Traits\Controllers\Crud;

    /**
     * These MUST be defined in your controller.
     * Here is a typical format.
     *
     * protected $list_route = '/admin/items';
     * protected $create_item_route = '/admin/item/create';
     * protected $get_item_route = '/admin/item/read/{id}';
     * protected $edit_item_route = '/admin/item/edit/{id}';
     */
    abstract protected function getModel();

    abstract protected function getItem();

    abstract protected function displayCreate();

    abstract protected function displayRead();

    abstract protected function displayEdit();

    protected function getItemKey()
    {
        if (empty($this->crud_item_key))
        {
            $this->crud_item_key = $this->getModel()->getItemKey();
        }
        
        if (empty($this->crud_item_key))
        {
            throw new \Exception('Must define an item key');
        }
        
        return $this->crud_item_key;
    }

    protected function doCreate(array $data)
    {
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $f3->set('flash', $flash);
        
        $use_flash = \Dsc\System::instance()->getUserState('use_flash.' . $this->create_item_route);
        if (!$use_flash)
        {
            $flash->store(array());
        }
        \Dsc\System::instance()->setUserState('use_flash.' . $this->create_item_route, false);
        
        $model = $this->getModel();
        $f3->set('model', $model);
        $this->displayCreate();
        
        return $this;
    }

    protected function doEdit(array $data)
    {
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $f3->set('flash', $flash);
        
        $model = $this->getModel();
        $item = $this->getItem();
        
        $f3->set('model', $model);
        $f3->set('item', $item);
        
        $use_flash = \Dsc\System::instance()->getUserState('use_flash.' . $this->edit_item_route);
        if (!$use_flash)
        {
            $item_data = array();
            if (method_exists($item, 'cast'))
            {
                $item_data = $item->cast();
            }
            elseif (is_object($item))
            {
                $item_data = \Joomla\Utilities\ArrayHelper::fromObject($item);
            }
            $flash->store($item_data);
        }
        \Dsc\System::instance()->setUserState('use_flash.' . $this->edit_item_route, false);
        
        $this->displayEdit();
        
        return $this;
    }

    protected function doRead(array $data, $key = null)
    {
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $f3->set('flash', $flash);
        
        $model = $this->getModel();
        $item = $this->getItem();
        
        $f3->set('model', $model);
        $f3->set('item', $item);
        
        $item_data = array();
        if (method_exists($item, 'cast'))
        {
            $item_data = $item->cast();
        }
        elseif (is_object($item))
        {
            $item_data = \Joomla\Utilities\ArrayHelper::fromObject($item);
        }
        $flash->store($item_data);
        
        $this->displayRead();
        
        return $this;
    }

    protected function doAdd($data)
    {
        if (empty($this->list_route))
        {
            throw new \Exception('Must define a route for listing the items');
        }
        
        if (empty($this->create_item_route))
        {
            throw new \Exception('Must define a route for creating the item');
        }
        
        if (empty($this->edit_item_route))
        {
            throw new \Exception('Must define a route for editing the item');
        }
        
        if (!isset($data['submitType']))
        {
            $data['submitType'] = "save_edit";
        }
        
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $model = $this->getModel();
        
        // save
        try
        {
            $values = $data;
            unset($values['submitType']);
            // \Dsc\System::instance()->addMessage(\Dsc\Debug::dump($values), 'warning');
            $this->item = $model->create($values);
        }
        catch (\Exception $e)
        {
            \Dsc\System::instance()->addMessage('Save failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            if (\Base::instance()->get('DEBUG'))
            {
                \Dsc\System::instance()->addMessage($e->getTraceAsString(), 'error');
            }
            
            if ($f3->get('AJAX'))
            {
                // output system messages in response object
                return $this->outputJson($this->getJsonResponse(array(
                    'error' => true,
                    'message' => \Dsc\System::instance()->renderMessages()
                )));
            }
            
            // redirect back to the create form with the fields pre-populated
            \Dsc\System::instance()->setUserState('use_flash.' . $this->create_item_route, true);
            $flash->store($data);
            
            $custom_redirect = !empty($data['__return']) ? base64_decode($data['__return']) : null;
            $route = $custom_redirect ? $custom_redirect : $this->create_item_route;
            
            $this->setRedirect($route);
            
            return false;
        }
        
        // redirect to the editing form for the new item
        \Dsc\System::instance()->addMessage('Item saved', 'success');
        
        if (method_exists($this->item, 'cast'))
        {
            $this->item_data = $this->item->cast();
        }
        else
        {
            $this->item_data = \Joomla\Utilities\ArrayHelper::fromObject($this->item);
        }
        
        if ($f3->get('AJAX'))
        {
            return $this->outputJson($this->getJsonResponse(array(
                'message' => \Dsc\System::instance()->renderMessages(),
                'result' => $this->item_data
            )));
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
                $id = $this->item->get($this->getItemKey());
                $route = str_replace('{id}', $id, $this->edit_item_route);
                break;
        }
        
        $custom_redirect = !empty($data['__return']) ? base64_decode($data['__return']) : null;
        $route = $custom_redirect ? $custom_redirect : $route;
        
        $this->setRedirect($route);
        
        return $this;
    }

    protected function doUpdate(array $data, $key = null)
    {
        if (empty($this->list_route))
        {
            throw new \Exception('Must define a route for listing the items');
        }
        
        if (empty($this->create_item_route))
        {
            throw new \Exception('Must define a route for creating the item');
        }
        
        if (empty($this->edit_item_route))
        {
            throw new \Exception('Must define a route for editing the item');
        }
        
        if (!isset($data['submitType']))
        {
            $data['submitType'] = "save_edit";
        }
        
        $f3 = \Base::instance();
        $flash = \Dsc\Flash::instance();
        $model = $this->getModel();
        $this->item = $this->getItem();
        
        // save
        $save_as = false;
        try
        {
            $values = $data;
            unset($values['submitType']);
            // \Dsc\System::instance()->addMessage(\Dsc\Debug::dump($values), 'warning');
            if ($data['submitType'] == 'save_as')
            {
                $this->item = $this->item->saveAs($values);
                \Dsc\System::instance()->addMessage('Item cloned. You are now editing the new item.', 'success');
            }
            else
            {
                $this->item = $this->item->update($values);
                \Dsc\System::instance()->addMessage('Item updated', 'success');
            }
        }
        catch (\Exception $e)
        {
            \Dsc\System::instance()->addMessage('Save failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            if (\Base::instance()->get('DEBUG'))
            {
                \Dsc\System::instance()->addMessage($e->getTraceAsString(), 'error');
            }
            
            if ($f3->get('AJAX'))
            {
                // output system messages in response object
                return $this->outputJson($this->getJsonResponse(array(
                    'error' => true,
                    'message' => \Dsc\System::instance()->renderMessages()
                )));
            }
            
            // redirect back to the edit form with the fields pre-populated
            \Dsc\System::instance()->setUserState('use_flash.' . $this->edit_item_route, true);
            $flash->store($data);
            $id = $this->item->get($this->getItemKey());
            $route = str_replace('{id}', $id, $this->edit_item_route);
            
            $custom_redirect = !empty($data['__return']) ? base64_decode($data['__return']) : null;
            $route = $custom_redirect ? $custom_redirect : $route;
            
            $this->setRedirect($route);
            
            return false;
        }
        
        // redirect to the editing form for the new item
        if (method_exists($this->item, 'cast'))
        {
            $this->item_data = $this->item->cast();
        }
        else
        {
            $this->item_data = \Joomla\Utilities\ArrayHelper::fromObject($this->item);
        }
        
        if ($f3->get('AJAX'))
        {
            return $this->outputJson($this->getJsonResponse(array(
                'message' => \Dsc\System::instance()->renderMessages(),
                'result' => $this->item_data
            )));
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
                $id = $this->item->get($this->getItemKey());
                $route = str_replace('{id}', $id, $this->edit_item_route);
                break;
        }
        
        $custom_redirect = !empty($data['__return']) ? base64_decode($data['__return']) : null;
        $route = $custom_redirect ? $custom_redirect : $route;
        
        $this->setRedirect($route);
        
        return $this;
    }

    protected function doDelete(array $data, $key = null)
    {
        if (empty($this->list_route))
        {
            throw new \Exception('Must define a route for listing the items');
        }
        
        $custom_redirect = !empty($data['__return']) ? base64_decode($data['__return']) : null;
        $redirect = $custom_redirect ? $custom_redirect : $this->list_route;
        
        $f3 = \Base::instance();
        $model = $this->getModel();
        $this->item = $this->getItem();
        
        try
        {
            $this->item->remove();
            \Dsc\System::instance()->addMessage('Item deleted', 'success');
            
            if ($f3->get('AJAX'))
            {
                return $this->outputJson($this->getJsonResponse(array(
                    'message' => \Dsc\System::instance()->renderMessages()
                )));
            }
        }
        catch (\Exception $e)
        {
            \Dsc\System::instance()->addMessage('Delete failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            if (\Base::instance()->get('DEBUG'))
            {
                \Dsc\System::instance()->addMessage($e->getTraceAsString(), 'error');
            }
            
            if ($f3->get('AJAX'))
            {
                // output system messages in response object
                return $this->outputJson($this->getJsonResponse(array(
                    'error' => true,
                    'message' => \Dsc\System::instance()->renderMessages()
                )));
            }
        }
        
        \Dsc\System::instance()->get('session')->set('delete.redirect', null);
        $this->setRedirect($redirect);
        
        return $this;
    }

    public function editInline()
    {
        try
        {
            $id = $this->inputfilter->clean($this->app->get('POST.pk'), 'alnum');
            $name = $this->inputfilter->clean($this->app->get('POST.name'), 'string');
            $value = $this->inputfilter->clean($this->app->get('POST.value'), 'string');
            
            if (empty($id) || empty($name) || empty($value))
            {
                throw new \Exception('One of your values is empty');
            }
            
            if (!$this->canUpdate(array(
                'id' => $id,
                'name' => $name,
                'value' => $value
            ), $this->getItemKey()))
            {
                throw new \Exception('Not allowed to edit record');
            }
            
            $mongoItem = $model = $this->getModel()
                ->setState('filter.id', $id)
                ->getItem();
            
            $original = $mongoItem->get($name);
            $mongoItem->set($name, $value);
            $mongoItem->save();
            header("Content-type: application/json; charset=utf-8");
            
            echo json_encode(array(
                'success' => true,
                'original' => $original
            ));
            
            exit();
        }
        catch (\Exception $e)
        {
            $this->app->error(404);
            echo json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
    
    public function translate()
    {
        // using $id and $code, create a clone of the object in the new language
        $id = $this->app->get('PARAMS.id');
        $code = $this->app->get('PARAMS.code');
        
        try {
            $item = $model = $this->getModel()->setState('filter.id', $id)->getItem();
            $clone = $item->set('id', null)->set('_id', null)->setLang( $code )->set('type', $item->type() )->save();
            
            \Dsc\System::addMessage('Translation created.  You are now editing the translation.');
            
            $new_id = $clone->get($this->getItemKey());
            $route = str_replace('{id}', $new_id, $this->edit_item_route);
            
            $this->app->reroute( $route );
            
        }
        catch (\Exception $e)
        {
            \Dsc\System::addMessage('Translation failed with the following errors:', 'error');
            \Dsc\System::addMessage($e->getMessage(), 'error');            
            $route = str_replace('{id}', $id, $this->edit_item_route);
            $this->app->reroute( $route );
        }        
        
    }
}