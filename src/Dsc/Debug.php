<?php 
namespace Dsc;

class Debug
{
	/**
	* Method to dump the structure of a variable for debugging purposes
	*
	* @param	mixed	A variable
	* @param	boolean	True to ensure all characters are htmlsafe
	* @return	string
	* @since	1.5
	* @static
	*/
	public static function dump( $var, $public_only = true, $ignore_underscore = true, $htmlSafe = true )
	{
	    if (!$public_only)
	    {
	        $result = self::_dump( $var, $public_only, $ignore_underscore );
	        return '<pre>'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	    }
	     
	    if (!is_object($var) && !is_array($var))
	    {
	        $result = self::_dump( $var, $public_only, $ignore_underscore );
	        return '<pre>'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	    }
	    	     
	    // TODO do a recursive remove of underscored keys, rather than only two levels
	    if (is_object($var))
	    {
	        $keys = get_object_vars($var);
	        foreach ($keys as $key=>$value)
	        {
	            if (substr($key, 0, 1) == '_' )
	            {
	                unset($var->$key);
	            }
	            else
	            {
	                if (is_object($var->$key))
	                {
	                    $sub_keys = get_object_vars($var->$key);
	                    foreach ($sub_keys as $sub_key=>$sub_key_value)
	                    {
	                        if (substr($sub_key, 0, 1) == '_')
	                        {
	                            unset($var->$key->$sub_key);
	                        }
	                    }
	                }
	                elseif (is_array($var->$key))
	                {
	                    foreach ($var->$key as $sub_key=>$sub_key_value)
	                    {
	                        if (substr($sub_key, 0, 1) == '_')
	                        {
	                            unset($var->$key[$sub_key]);
	                        }
	                    }
	                }
	            }
	             
	             
	        }
	        $result = self::_dump( $var, $public_only, $ignore_underscore );
	        return '<pre>'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	    }
	     
	    if (is_array($var))
	    {
	        foreach ($var as $key=>$value)
	        {
	            if (substr($key, 0, 1) == '_')
	            {
	                unset($var[$key]);
	            }
	            else
	            {
	                if (is_object($var[$key]))
	                {
	                    $sub_keys = get_object_vars($var[$key]);
	                    foreach ($sub_keys as $sub_key=>$sub_key_value)
	                    {
	                        if (substr($sub_key, 0, 1) == '_')
	                        {
	                            unset($var[$key]->$sub_key);
	                        }
	                    }
	                }
	                elseif (is_array($var[$key]))
	                {
	                    foreach ($var[$key] as $sub_key=>$sub_key_value)
	                    {
	                        if (substr($sub_key, 0, 1) == '_')
	                        {
	                            unset($var[$key][$sub_key]);
	                        }
	                    }
	                }
	            }
	        }
	        $result = self::_dump( $var, $public_only, $ignore_underscore );
	        return '<pre>'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	    }
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $data
	 */
	private static function _dump( $var, $public_only=true, $ignore_underscore = true )
	{
	    $data = @print_r( $var, true );
	    //return $data;

	    $lines = explode("\n", $data);
	    $key = 0;

	    //foreach ($lines as $key=>$line)
	    while (isset($lines[$key]))
	    {
	        $line = $lines[$key];
	        $is_protected = false;
	        if (strpos($line, ':protected]') !== false)
	        {
	            $is_protected = true;
	        }

	        if ($is_protected && $public_only)
	        {
	            // unset this one
	            unset($lines[$key]);

	            // is this an array or object?
	            // if so, unset all the next lines until the array/object is done

	            $nextkey = $key + 1;
	            if (trim($lines[$nextkey]) == '(')
	            {
	                // count the spaces at the beginning of the line
	                $count = substr_count(rtrim($lines[$nextkey]), ' ');

	                unset($lines[$nextkey]);
	                $key = $nextkey;

	                $next_line_key = $nextkey + 1;
	                $next_line_space_count = substr_count(rtrim($lines[$next_line_key]), ' ');

	                while (trim($lines[$next_line_key]) != ')' || $next_line_space_count != $count)
	                {
	                    unset($lines[$next_line_key]);
	                    $next_line_key = $next_line_key + 1;
	                    $next_line_space_count = substr_count(rtrim($lines[$next_line_key]), ' ');
	                }

	                if (trim($lines[$next_line_key]) == ')' && $next_line_space_count == $count)
	                {
	                    unset($lines[$next_line_key]);
	                    $key = $next_line_key;
	                }
	            }
	        }

	        $key++;
	    }

	    foreach ($lines as $key=>$line)
	    {
	        if (empty($lines[$key])) {
	            unset($lines[$key]);
	        }
	    }

	    $out = implode("\n", $lines) . "\n";
	    return $out;
	}
}
?>