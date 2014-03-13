<?php
namespace Dsc\Traits\Controllers;
 
trait Settings
{
    /**
     * These MUST be defined in your controller.
     * Here is a typical format.
     *
     protected $layout_link = 'Blog/Admin/Views::settings/default.php';
     protected $settings_route = '/admin/blog/settings';
     */
    
    abstract protected function getModel();

    public function index()
    {
    	\Base::instance()->set('pagetitle', 'Settings');
    	\Base::instance()->set('subtitle', '');
    
    	$f3 = \Base::instance();
    	$flash = \Dsc\Flash::instance();
    	$f3->set('flash', $flash );
    
    	$model = $this->getModel();
    	$item = $this->getItem();
    
    	$f3->set('model', $model );
    	$f3->set('item', $item );
    
    	$item_data = $model->prefab()->cast();
    	if (method_exists($item, 'cast')) {
    		$item_data = $item->cast();
    	} elseif (is_object($item)) {
    		$item_data = \Joomla\Utilities\ArrayHelper::fromObject($item);
    	}
    	$flash->store($item_data);
    
    	$view = \Dsc\System::instance()->get('theme');
    	echo $view->render($this->layout_link);
    }
    
    public function save()
    {
    	$f3 = \Base::instance();
    	$flash = \Dsc\Flash::instance();
    	$data = $f3->get('REQUEST');
    	$model = $this->getModel();
    	$this->item = $this->getItem();
    
    	// save
    	$save_as = false;
    	try {
    		$values = $data;
    		unset($values['submitType']);
    
    		if (empty($this->item->id)) {
    			$this->item = $model->create($values);
    			\Dsc\System::instance()->addMessage('Settings saved');
    		} else {
    			$this->item = $model->update($this->item, $values);
    			\Dsc\System::instance()->addMessage('Settings updated');
    		}
    	}
    	catch (\Exception $e)
    	{
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
    
    		// redirect back to the form with the fields pre-populated
    		$flash->store($data);
    		$f3->reroute( $this->settings_route );
    
    		return;
    	}
    
    	if ($f3->get('AJAX'))
    	{
    		if (method_exists($this->item, 'cast')) {
    			$this->item_data = $this->item->cast();
    		} else {
    			$this->item_data = \Joomla\Utilities\ArrayHelper::fromObject($this->item);
    		}
    
    		return $this->outputJson( $this->getJsonResponse( array(
    				'message' => \Dsc\System::instance()->renderMessages(),
    				'result' => $this->item_data
    		) ) );
    	}
    
    	$f3->reroute( $this->settings_route );
    
    	return;
    }
    
    protected function getItem()
    {
    	$f3 = \Base::instance();
    	$model = $this->getModel()
    	->setState('filter.type', true);
    
    	try {
    		$item = $model->getItem();
    	} catch ( \Exception $e ) {
    		\Dsc\System::instance()->addMessage( "Invalid Item: " . $e->getMessage(), 'error');
    		$f3->reroute( $this->settings_route );
    		return;
    	}
    
    	return $item;
    }
}
?>