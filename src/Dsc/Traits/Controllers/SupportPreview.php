<?php
namespace Dsc\Traits\Controllers;
 
trait SupportPreview 
{
    /**
     * Checks, if the current user has permission to preview content
     * 
     * @param string $onlyTest 	If True, only test for permission is carried out (no redirection in case of missing permissions)
     */
    private function canPreview( $onlyTest = false, $force_resource = null ) 
    {
    	$resource_name = $force_resource;
    	if( $resource_name == null ){
    		$resource_name = get_class( $this->getModel() );
    	}
    	// add resource and resource actioon, if needed
    	\Dsc\System::instance()->get( 'acl' )->getAcl()->addResourceAction( $resource_name, 'Preview' );
    	 
    	if( $onlyTest ){
	    	$identity = $this->getIdentity();
	   		$this->requireIdentity();
    	 
    		return \Dsc\System::instance()->get('acl')->isAllowed($identity->role, $resource_name, 'Preview');
    	} else {
    		return $this->checkAccess( $resource_name, 'Preview' );
    	}
    }
}
?>