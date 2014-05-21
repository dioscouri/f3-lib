<?php
namespace Dsc\Traits\Controllers;
 
trait EnablableItem
{
    /**
     * These MUST be defined in your controller.
     * Here is a typical format.
     *
     protected $list_route = '/admin/items';
	
	 
	 protected function getModel();
	 protected function canUpdate($item); // in trait AdminList
     */
	
	public function EnablableItemChangeStateItemEnable()
	{
		if (empty($this->list_route)) {
			throw new \Exception('Must define a route for listing the items');
		}
	
		$f3 = \Base::instance();
		$id = $this->inputfilter->clean( $f3->get('PARAMS.id' ), 'alnum' );
	
		if( empty( $id ) ) {
			\Dsc\System::instance()->addMessage('No items selected to change state.', 'warning');
		} else {
			$this->EnablableItemChangeState( array( $id ), 1 );
		}
		$f3->reroute( $this->list_route );
	}
	
	public function EnablableItemChangeStateItemDisable()
	{
		if (empty($this->list_route)) {
			throw new \Exception('Must define a route for listing the items');
		}
	
		$f3 = \Base::instance();
		$id = $this->inputfilter->clean( $f3->get('PARAMS.id' ), 'alnum' );
	
		if( empty( $id ) ) {
			\Dsc\System::instance()->addMessage('No items selected to change state.', 'warning');
		} else {
			$this->EnablableItemChangeState( array( $id ), 0 );
		}
		$f3->reroute( $this->list_route );
	}
	
	public function EnablableItemChangeStateEnable()
	{
		if (empty($this->list_route)) {
			throw new \Exception('Must define a route for listing the items');
		}
		
		$f3 = \Base::instance();
		$data = $f3->get('REQUEST');
		$selected  = $this->EnablableItemExtractAllIds( $data['ids'] );
		
		if( empty( $selected ) ) {
			\Dsc\System::instance()->addMessage('No items selected to change state.', 'warning');
		} else {
			$this->EnablableItemChangeState( $ids, 1 );
		}
       	$f3->reroute( $this->list_route );
	}
	
	public function EnablableItemChangeStateDisable()
	{
		if (empty($this->list_route)) {
			throw new \Exception('Must define a route for listing the items');
		}
	
		$f3 = \Base::instance();
		$data = $f3->get('REQUEST');
		$selected  = $this->EnablableItemExtractAllIds( $data['ids'] );
	
		if( empty( $selected ) ) {
			\Dsc\System::instance()->addMessage('No items selected to change state.', 'warning');
		} else {
			$this->EnablableItemChangeState( $ids, 0 );
		}
		$f3->reroute( $this->list_route );
	}
	
	
	/**
	 * Returns list of ids to be used
	 * 
	 * @param unknown $ids
	 * @return List of ids
	 */
	private function EnablableItemExtractAllIds( $ids ){
		if (empty( $ids ) ) {
			return array();
		} else {
			$selected = array();
			$input = (array) $ids;
			foreach ($input as $id)
			{
				if ($id = $this->inputfilter->clean( $id, 'alnum' )) {
					$selected[] = $id;
				}
			}
			return $selected;
		}
	}
	
    /**
     * Enables or disables all records
     */
    protected function EnablableItemChangeState($ids, $state)
    {
		$model = $this->getModel();
		if ($items = $model->setState('filter.ids', $ids)->getList())
		{
			foreach ($items as $item)
			{
				if ($this->canUpdate($item)) {
					try {
						$item->enabled = $state;
						$item->save();
					} catch (\Exception $e) {
						$this->setError(true);
						\Dsc\System::instance()->addMessage('Change of state has failed with the following errors:', 'error');
                        foreach ($item->getErrors() as $error)
                        {
	                      	\Dsc\System::instance()->addMessage($error, 'error');
						}
					}
				} else {
                	$this->setError(true);
                    \Dsc\System::instance()->addMessage('Not allowed to update this record.', 'error');                
				}
			}
                    
			if (!$errors = $this->getErrors()) 
			{
				\Dsc\System::instance()->addMessage('State of items have been changed');
			}
		}
         
        return;
    }
}
?>