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
	public function moveUp()
    {
    	$item = $this->getModel()->getItem();
        if (!$this->canUpdate( $item )) {
            throw new \Exception('Not allowed to update record');
        }
        
        $this->doMoveUp( $item );

        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    public function moveDown()
    {
        $item = $this->getModel()->getItem();
        if (!$this->canUpdate($item )) {
            throw new \Exception('Not allowed to update record');
        }
        
        $this->doMoveDown( $item );
        
        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    protected function doMoveUp($item )
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
    
        $f3 = \Base::instance();
    
        try {
            $item->moveUp();
            \Dsc\System::instance()->addMessage('Item moved up');
        }
        catch (\Exception $e) {
            \Dsc\System::instance()->addMessage('Item move failed with the following errors:', 'error');
            \Dsc\System::instance()->addMessage($e->getMessage(), 'error');
            foreach ($item->getErrors() as $error)
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
    
    protected function doMoveDown( $item )
    {
        if (empty($this->list_route)) {
            throw new \Exception('Must define a route for listing the items');
        }
    
        $f3 = \Base::instance();
    
        try {
            $item->moveDown();
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