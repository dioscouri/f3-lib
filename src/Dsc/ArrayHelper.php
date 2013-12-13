<?php 
namespace Dsc;

class ArrayHelper extends \Prefab
{
	/**
	 * Get an item from an array using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function get($array, $key, $default=null)
	{
		if (is_null($key)) 
		{
		    return $array;
		}

		if (isset($array[$key])) 
		{
		    return $array[$key];
		}

		foreach (explode('.', $key) as $segment)
		{
			if ( !is_array($array) || !array_key_exists($segment, $array))
			{
				return $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}
	
	/**
	 * Set an array item to a given value using dot notation
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	public static function set(&$array, $key, $value=null)
	{
	    if (is_null($key)) {
	        return $array = $value;
	    }
	
	    $keys = explode('.', $key);
	
	    while (count($keys) > 1)
	    {
	        $key = array_shift($keys);
	
	        if ( !isset($array[$key]) || !is_array($array[$key]))
	        {
	            $array[$key] = array();
	        }
	
	        $array =& $array[$key];
	    }
	
	    $array[array_shift($keys)] = $value;
	
	    return $array;
	}
	
	/**
	 * Get a subset of the items from the given array
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	public static function only($array, $keys)
	{
	    return array_intersect_key($array, array_flip((array) $keys));
	}
	
	/**
	 * Remove an array item from a given array using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return void
	 */
	public static function forget(&$array, $key)
	{
	    $keys = explode('.', $key);
	    $count = count($keys);
	    
	    while ($count > 1)
	    {
	        $key = array_shift($keys);
	
	        if ( !isset($array[$key]) || !is_array($array[$key]))
	        {
	            return;
	        }
	
	        $array =& $array[$key];
	    }
	
	    unset($array[array_shift($keys)]);
	}
	
	/**
	 * Get an item from an array using dot notation
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function exists($array, $key)
	{
	    if (isset($array[$key]))
	    {
	        return true;
	    }
	
	    foreach (explode('.', $key) as $segment)
	    {
	        if ( !is_array($array) || !array_key_exists($segment, $array))
	        {
	            return false;
	        }
	
	        $array = $array[$segment];
	    }
	
	    return true;
	}	
}