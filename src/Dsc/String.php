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
    
    /**
     *	Format phone  string
     *
     *	@return string
     *	@param $str string
     **/
    public static function format_phone($phone)
    {
    	$phone = preg_replace("/[^0-9]/", "", $phone);
    
    	if(strlen($phone) == 7)
    		return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
    	elseif(strlen($phone) == 10)
    	return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
    	else
    		return $phone;
    }
}