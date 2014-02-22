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
	public static function dump( $var, $public_only=true, $htmlSafe = true )
	{
        $result = self::_dump( $var, $public_only );
        return '<pre>'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $data
	 */
	private static function _dump( $var, $public_only=true )
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
	        if (strpos($line, ':protected]') !== false || strpos($line, ':private]') !== false)
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