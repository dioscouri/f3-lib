<?php
namespace Dsc\Traits\Controllers;

/**
 * Adds target functions for reordering items, moving them up/down in a list. 
 * 
 * @author Rafael Diaz-Tushman
 *
 */
trait OrderableItemCollection 
{
    use \Dsc\Traits\Controllers\CrudItemCollection;
    
    public function moveUp()
    {
        $data = \Base::instance()->get('REQUEST');
        
        if (!$this->canUpdate($data, $this->getItemKey())) {
            throw new \Exception('Not allowed to update record');
        }
        
        $this->doMoveUp($data, $this->getItemKey());

        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    public function moveDown()
    {
        $data = \Base::instance()->get('REQUEST');
        
        if (!$this->canUpdate($data, $this->getItemKey())) {
            throw new \Exception('Not allowed to update record');
        }
        
        $this->doMoveDown($data, $this->getItemKey());
        
        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    protected function doMoveUp(array $data, $key=null)
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
    
        $f3 = \Base::instance();
    
        try {
            $this->item = $this->getItem()->moveUp();
            \Dsc\System::instance()->addMessage('Item moved up');
        }
        catch (\Exception $e) {
            \Dsc\System::instance()->addMessage('Item move failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            foreach ($this->item->getErrors() as $error)
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
    
    protected function doMoveDown(array $data, $key=null)
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
    
        $f3 = \Base::instance();
    
        try {
            $this->item = $this->getItem()->moveDown();
            \Dsc\System::instance()->addMessage('Item moved down');
        }
        catch (\Exception $e) {
            \Dsc\System::instance()->addMessage('Item move failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            foreach ($this->item->getErrors() as $error)
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