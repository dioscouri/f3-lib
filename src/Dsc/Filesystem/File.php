<?php 
namespace Dsc\Filesystem;

class File
{
    /**
     * Case-insensitive file_exists
     * 
     * Note: this is much heavier than php's file_exists.  If you don't need to check case sensitivity, then don't use this
     * 
     */
    public static function exists( $path, $caseSensitive=false )
    {
        if (file_exists($path)) 
        {
            return true;
        }
        
        if ($caseSensitive) 
        {
            return false;
        }

        if (!$directoryName = Dsc\Filesystem\Path::real( dirname($path) )) 
        {
        	return false;
        }
        
        $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
        $fileNameLowerCase = strtolower(basename($path));
        foreach($fileArray as $file) {
            if(strtolower($file) == $fileNameLowerCase) {
                return true;
            }
        }
        
        return false;
    }
}