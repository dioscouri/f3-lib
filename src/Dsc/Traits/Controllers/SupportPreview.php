<?php
namespace Dsc\Traits\Controllers;
 
trait SupportPreview 
{
    /**
     * Checks, if the current user has permission to preview content
     * 
     * @param string $onlyTest 	If True, only test for permission is carried out (no redirection in case of missing permissions)
     */
    private function canPreview( $onlyTest = false ) 
    {
    	$this->addPreviewResourceAction();
    	$model_name = get_class( $this->getModel() );
    	
    	if( $onlyTest ){
	    	$identity = $this->getIdentity();
	   		$this->requireIdentity();
    	 
    		return \Dsc\System::instance()->get('acl')->isAllowed($identity->role, $model_name, 'Preview');
    	} else {
    		return $this->checkAccess( $model_name, 'Preview' );
    	}
    }
    
    /**
     * Adds resource, resource action, if needed
     */
    private function addPreviewResourceAction(){
    	$model_name = get_class( $this->getModel() );
    	
    	$acl = \Dsc\System::instance()->get( 'acl' )->getAcl();
    	$resource = new \Users\Lib\Acl\Resource( $model_name);
    	$acl->addResource( $resource );
    }
    
}
?>