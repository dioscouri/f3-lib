<?php
namespace Dsc;

class ArrayHelper extends \Prefab
{

    /**
     * Get an item from an array using dot notation
     *
     * @param array $array            
     * @param string $key            
     * @param mixed $default            
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (is_null($key))
        {
            return $array;
        }
        
        if (is_object($array))
        {
            $array = \Joomla\Utilities\ArrayHelper::fromObject($array);
        }
        
        if (isset($array[$key]))
        {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment)
        {
            if (is_object($array))
            {
                $array = \Joomla\Utilities\ArrayHelper::fromObject($array);
            }
            
            if (!is_array($array) || !array_key_exists($segment, $array))
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
     * @param array $array            
     * @param string $key            
     * @param mixed $value            
     * @return array
     */
    public static function set(&$array, $key, $value = null)
    {
        if (is_null($key))
        {
            return $array = $value;
        }
        
        $keys = explode('.', $key);
        
        while (count($keys) > 1)
        {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key]))
            {
                $array[$key] = array();
            }
            
            $array = & $array[$key];
        }
        
        $array[array_shift($keys)] = $value;
        
        return $array;
    }

    /**
     * Get a subset of the items from the given array
     *
     * @param array $array            
     * @param array $keys            
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Remove an array item from a given array using dot notation
     *
     * @param array $array            
     * @param string $key            
     * @return void
     */
    public static function clear(&$array, $key)
    {
        $keys = explode('.', $key);
        $count = count($keys);
        
        while ($count > 1)
        {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key]))
            {
                return;
            }
            
            $array = & $array[$key];
        }
        
        unset($array[array_shift($keys)]);
    }

    /**
     * Get an item from an array using dot notation
     *
     * @param array $array            
     * @param string $key            
     * @param mixed $default            
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
            if (!is_array($array) || !array_key_exists($segment, $array))
            {
                return false;
            }
            
            $array = $array[$segment];
        }
        
        return true;
    }

    /**
     * Filter an array using the provided Closure callback function
     *
     * @param array $array            
     * @param \Closure $callback            
     * @param boolean $useCallbackReturn            
     * @return array
     */
    public static function where($array, \Closure $callback, $useCallbackReturn = true)
    {
        $filtered = array();
        
        foreach ($array as $key => $value)
        {
            if ($return = call_user_func($callback, $key, $value))
            {
                if ($useCallbackReturn === false)
                {
                    $filtered[] = $value;
                }
                else
                {
                    $filtered[] = $return;
                }
            }
        }
        
        return $filtered;
    }

    /**
     * Fetch a flattened array of a nested array element using dot notation
     *
     * @param array $array            
     * @param string $key            
     * @return array
     */
    public static function fetch($array, $key)
    {
        foreach (explode('.', $key) as $segment)
        {
            $results = array();
            
            foreach ($array as $value)
            {
                $value = (array) $value;
                
                $results[] = $value[$segment];
            }
            
            $array = array_values($results);
        }
        
        return array_values($results);
    }

    /**
     * Flatten a multi-dimensional associative array with dots to indicate depth
     *
     * @param array $array            
     * @param string $prepend            
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = array();
        
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            }
            else
            {
                $results[$prepend . $key] = $value;
            }
        }
        
        return $results;
    }

    /**
     * Flatten a multi-dimensional array into a single level
     *
     * @param array $array            
     * @return array
     */
    public static function flatten($array, $recursive = false)
    {
        $return = array();
        
        if ($recursive)
        {
            array_walk_recursive($array, function ($x) use(&$return) {
                $return[] = $x;
            });
        }
        else
        {
            array_walk($array, function ($x) use(&$return) {
                $return[] = $x;
            });
        }
        
        return $return;
    }

    /**
     * Fetch all values from an array that are at a particular depth in the source array
     *
     * @param array $array            
     * @return array
     */
    public static function level($array, $level = 1)
    {
        $return = array();
        
        for ($n = 0; $n < $level; $n++)
        {
            array_walk($array, function ($x) use(&$return) {
                $return = array_merge(array(), array_values($x));
            });
            $array = $return;
        }
        
        return $array;
    }

    /**
     * Sort an array of arrays
     *
     * @param array $a            
     * @param unknown $k            
     * @param number $direction            
     * @return Ambigous <number, array>|number|unknown
     */
    public static function sortArrays(array $a, $k, $direction = 1)
    {
        $sortDirection = (array) $direction;
        $key = (array) $k;
        
        usort($a, function ($a, $b) use($sortDirection, $key) {
            for ($i = 0, $count = count($key); $i < $count; $i++)
            {
                if (isset($sortDirection[$i]))
                {
                    $direction = $sortDirection[$i];
                }
                
                $va = $a[$key[$i]];
                $vb = $b[$key[$i]];
                
                if ((is_bool($va) || is_numeric($va)) && (is_bool($vb) || is_numeric($vb)))
                {
                    $cmp = $va - $vb;
                }
                else
                {
                    $cmp = strcasecmp($va, $vb);
                }
                
                if ($cmp > 0)
                {
                    return $direction;
                }
                
                if ($cmp < 0)
                {
                    return -$direction;
                }
            }
            
            return 0;
        });
        
        return $a;
    }

    /**
     * Utility function to sort an array of objects on a given field
     *
     * @param array $a
     *            An array of objects
     * @param mixed $k
     *            The key (string) or a array of key to sort on
     * @param mixed $direction
     *            Direction (integer) or an array of direction to sort in [1 = Ascending] [-1 = Descending]
     * @param mixed $caseSensitive
     *            Boolean or array of booleans to let sort occur case sensitive or insensitive
     * @param mixed $locale
     *            Boolean or array of booleans to let sort occur using the locale language or not
     *            
     * @return array The sorted array of objects
     *        
     * @since 1.0
     */
    public static function sortObjects(array $a, $k, $direction = 1, $caseSensitive = true, $locale = false)
    {
        if (!is_array($locale) || !is_array($locale[0]))
        {
            $locale = array(
                $locale
            );
        }
        
        $sortCase = (array) $caseSensitive;
        $sortDirection = (array) $direction;
        $key = (array) $k;
        $sortLocale = $locale;
        
        usort($a, function ($a, $b) use($sortCase, $sortDirection, $key, $sortLocale) {
            for ($i = 0, $count = count($key); $i < $count; $i++)
            {
                if (isset($sortDirection[$i]))
                {
                    $direction = $sortDirection[$i];
                }
                
                if (isset($sortCase[$i]))
                {
                    $caseSensitive = $sortCase[$i];
                }
                
                if (isset($sortLocale[$i]))
                {
                    $locale = $sortLocale[$i];
                }
                
                $va = $a->$key[$i];
                $vb = $b->$key[$i];
                
                if ((is_bool($va) || is_numeric($va)) && (is_bool($vb) || is_numeric($vb)))
                {
                    $cmp = $va - $vb;
                }
                elseif ($caseSensitive)
                {
                    $cmp = strcmp($va, $vb);
                }
                else
                {
                    $cmp = strcasecmp($va, $vb);
                }
                
                if ($cmp > 0)
                {
                    return $direction;
                }
                
                if ($cmp < 0)
                {
                    return -$direction;
                }
            }
            
            return 0;
        });
        
        return $a;
    }

    /**
     * Extracts a column from an array of arrays or objects
     *
     * @param array $array
     *            The source array
     * @param string $index
     *            The index of the column or name of object property
     *            
     * @return array Column of values from the source array
     */
    public static function getColumn(array $array, $index)
    {
        $result = array();
        
        foreach ($array as $item)
        {
            if (is_array($item) && isset($item[$index]))
            {
                $result[] = $item[$index];
            }
            elseif (is_object($item) && isset($item->$index))
            {
                $result[] = $item->$index;
            }
        }
        
        return $result;
    }
}