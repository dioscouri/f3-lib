<?php 
namespace Dsc;

class ObjectHelper extends \Prefab
{
	/**
	 * Get an item from an object using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get($object, $key, $default=null)
	{
	    $array = \Joomla\Utilities\ArrayHelper::fromObject($object);
	    return \Dsc\ArrayHelper::get($array, $key, $default);
	}
	
	/**
	 * Set an object property to a given value using dot notation
	 *
	 * If no key is given to the method, the entire object will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function set(&$object, $key=null, $value=null)
	{
	    if (is_null($key)) {
	        return $object = $value;
	    }
	    
	    $keys = explode('.', $key);
	    
	    while (count($keys) > 1) 
	    {
	        $key = array_shift($keys);
	    
	        if (is_object($object)) 
	        {
	            if (!isset($object->$key) or !is_array($object->$key)) {
	                $object->$key = array();
	            }
	            $object =& $object->$key;
	        } 
	           else 
	        {
	            if (!isset($object[$key]) or !is_array($object[$key])) {
	                $object[$key] = array();
	            }
	            $object =& $object[$key];
	        }
	    }
	    
	    $key = array_shift($keys);
	    if (is_array($object)) {
	        $object[$key] = $value;
	    }
	    else {
	        $object->$key = $value;
	    }

	    return $object;	    
	}
}