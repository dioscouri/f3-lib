<?php
namespace Dsc\Traits\Models;

trait OrderableItem 
{
    public function moveUp( $mapper )
    {
        return $mapper->moveUp();
    }
    
    public function moveDown( $mapper )
    {
        return $mapper->moveDown();
    }
}