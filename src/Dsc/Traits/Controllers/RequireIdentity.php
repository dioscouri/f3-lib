<?php
namespace Dsc\Traits\Controllers;

/**
 * Any private controllers -- which means: controllers that require authentication
 * in order to execute ANY AND ALL of their methods --
 * can use this trait.
 * 
 * If you override the beforeRoute method in your controller, remember to add $this->requireIdentity()
 * since the trait's beforeRoute() will no longer be triggered 
 * 
 * Alternatively, just run $this->requireIdentity() inside your restricted methods.
 *
 */
trait RequireIdentity
{
    public function beforeRoute()
    {
        $this->requireIdentity();
    }
}