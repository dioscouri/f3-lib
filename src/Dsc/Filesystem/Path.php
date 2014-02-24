<?php 
namespace Dsc\Filesystem;

class Path
{
    /**
     * Given a string, returns the Case Sensitive realpath to the matching string
     * so if you provide "/home/MyName/Some/foLDEr"
     * but the case sensitive real path is "/home/myname/some/Folder", 
     * the correct path will be returned
     * 
     */
    public static function real( $string )
    {
        if (is_dir($string)) {
        	return $string;
        }
        
        $path = str_replace("\\", "/", $string);
        // explode by /
        $pieces = explode("/", $path);
        $base = null;
        foreach ($pieces as $piece)
        {
            if (is_dir($base."/".$piece)) 
            {
            	$base = $base."/".$piece;
            }
            else 
            {
            	// find the correct folder name
                $fileArray = glob($base . '/*', GLOB_ONLYDIR|GLOB_NOSORT);
                $fileNameLowerCase = strtolower($piece);
                foreach($fileArray as $file) {
                    if(strtolower($file) == $fileNameLowerCase) {
                        $base = $base."/".$file;
                        break;
                    }
                }
                
                // if we're here, no match could be found, break & fail
                return false;
            }
        }
        
        if (empty($base)) {
        	return false;
        }
        return $base;
    }
    
    /**
     * Function to strip additional / or \ in a path name.
     *
     * @param   string  $path  The path to clean.
     * @param   string  $ds    Directory separator (optional).
     *
     * @return  string  The cleaned path.
     *
     * @since   1.0
     * @throws  \UnexpectedValueException If $path is not a string.
     */
    public static function clean($path, $ds = DIRECTORY_SEPARATOR)
    {
        if (!is_string($path))
        {
            throw new \UnexpectedValueException('\Dsc\Filesystem\Path::clean $path is not a string.');
        }
    
        $path = trim($path);
    
        if (($ds == '\\') && ($path[0] == '\\' ) && ( $path[1] == '\\' ))
        // Remove double slashes and backslashes and convert all slashes and backslashes to DIRECTORY_SEPARATOR
        // If dealing with a UNC path don't forget to prepend the path with a backslash.
        {
            $path = "\\" . preg_replace('#[/\\\\]+#', $ds, $path);
        }
        else
        {
            $path = preg_replace('#[/\\\\]+#', $ds, $path);
        }
    
        return $path;
    }
}