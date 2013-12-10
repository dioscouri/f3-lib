<?php 
namespace Dsc;

class String
{
    public static function inStrings($needles, $haystack)
    {
        foreach ($needles as $needle) 
        {
            if (strpos($haystack, $needle) !== false) 
            {
                return true;
            }
        }
        
        return false;
    }
}