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
    
    /**
     *	Split comma-, semicolon-, or pipe-separated string
     *
     *	@return array
     *	@param $str string
     **/
    public static function split($str)
    {
        return array_map('trim', preg_split('/[,;|]/',$str,0,PREG_SPLIT_NO_EMPTY));
    }
}