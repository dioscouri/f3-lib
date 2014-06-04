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
    	$model_name = get_class( $this->getModel() );
    	// add resource and resource actioon, if needed
    	\Dsc\System::instance()->get( 'acl' )->getAcl()->addResourceAction( $model_name, 'Preview' );
    	 
    	if( $onlyTest ){
	    	$identity = $this->getIdentity();
	   		$this->requireIdentity();
    	 
    		return \Dsc\System::instance()->get('acl')->isAllowed($identity->role, $model_name, 'Preview');
    	} else {
    		return $this->checkAccess( $model_name, 'Preview' );
    	}
    }
}
?>