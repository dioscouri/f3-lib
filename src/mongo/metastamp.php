<?php 
namespace Dsc\Mongo;

class Metastamp
{
    /**
     * Converts a datetime string into an array of useful info
     * great for storing in a Mongo document when you need to write very specific reports
     * 
     * Modeled after https://github.com/sporkd/mongoid-metastamp
     * 
     * @param unknown_type $time
     */
    public static function getDate( $time ) 
    {
        /**
         * getdate() returns:
         Array
         (
         [seconds] => 40
         [minutes] => 58
         [hours]   => 21
         [mday]    => 17 // int - day of month
         [wday]    => 2
         [mon]     => 6
         [year]    => 2003
         [yday]    => 167
         [weekday] => Tuesday
         [month]   => June
         [0]       => 1055901520 // int - seconds since unix epoch
         )
         */
                
        $strtotime = strtotime( $time );
        
        $array = array(
                'time' => $strtotime, 
                'local' => date('Y-m-d H:i:s', $strtotime),
                'utc' => gmdate('Y-m-d H:i:s', $strtotime),
                'offset' => date('P', $strtotime)
                ) + getdate( $strtotime );
        
        unset($array['weekday']);
        unset($array['month']);
        unset($array[0]);
        
        return $array;
    } 
}