<?php
namespace Dsc;

class Activities extends Singleton
{
    public static function track($action, $properties=array())
    {
        if (class_exists('\Activity\Models\Actions'))
        {
            \Activity\Models\Actions::track($action, $properties);
        }
    
        return null;
    }
}