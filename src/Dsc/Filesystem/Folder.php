<?php 
namespace Dsc\Filesystem;

class Folder
{
    /**
     * Utility function to read the folders in a folder.
     *
     * @param   string   $path           The path of the folder to read.
     * @param   string   $filter         A filter for folder names.
     * @param   mixed    $recurse        True to recursively search into sub-folders, or an integer to specify the maximum depth.
     * @param   boolean  $full           True to return the full path to the folders.
     * @param   array    $exclude        Array with names of folders which should not be shown in the result.
     * @param   array    $excludefilter  Array with regular expressions matching folders which should not be shown in the result.
     *
     * @return  array  Folders in the given folder.
     *
     * @since   1.0
     */
    public static function folders($path, $filter = '.', $recurse = false, $full = false, $exclude = array('.git', '.svn', 'CVS', '.DS_Store', '__MACOSX'), $excludefilter = array('^\..*'))
    {
        // Check to make sure the path valid and clean
        $path = \Dsc\Filesystem\Path::clean($path);
    
        // Is the path a folder?
        if (!is_dir($path))
        {
            return array();
        }
    
        // Compute the excludefilter string
        if (count($excludefilter))
        {
            $excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
        }
        else
        {
            $excludefilter_string = '';
        }
    
        // Get the folders
        $arr = self::_items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, false);
    
        // Sort the folders
        asort($arr);
    
        return array_values($arr);
    }
    
    /**
     * Function to read the files/folders in a folder.
     *
     * @param   string   $path                  The path of the folder to read.
     * @param   string   $filter                A filter for file names.
     * @param   mixed    $recurse               True to recursively search into sub-folders, or an integer to specify the maximum depth.
     * @param   boolean  $full                  True to return the full path to the file.
     * @param   array    $exclude               Array with names of files which should not be shown in the result.
     * @param   string   $excludefilter_string  Regexp of files to exclude
     * @param   boolean  $findfiles             True to read the files, false to read the folders
     *
     * @return  array  Files.
     *
     * @since   1.0
     */
    protected static function _items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles)
    {
        @set_time_limit(ini_get('max_execution_time'));
    
        $arr = array();
    
        // Read the source directory
        if (!($handle = @opendir($path)))
        {
            return $arr;
        }
    
        while (($file = readdir($handle)) !== false)
        {
            if ($file != '.' && $file != '..' && !in_array($file, $exclude)
            && (empty($excludefilter_string) || !preg_match($excludefilter_string, $file)))
            {
                // Compute the fullpath
                $fullpath = $path . '/' . $file;
    
                // Compute the isDir flag
                $isDir = is_dir($fullpath);
    
                if (($isDir xor $findfiles) && preg_match("/$filter/", $file))
                {
                    // (fullpath is dir and folders are searched or fullpath is not dir and files are searched) and file matches the filter
                    if ($full)
                    {
                        // Full path is requested
                        $arr[] = $fullpath;
                    }
                    else
                    {
                        // Filename is requested
                        $arr[] = $file;
                    }
                }
    
                if ($isDir && $recurse)
                {
                    // Search recursively
                    if (is_int($recurse))
                    {
                        // Until depth 0 is reached
                        $arr = array_merge($arr, self::_items($fullpath, $filter, $recurse - 1, $full, $exclude, $excludefilter_string, $findfiles));
                    }
                    else
                    {
                        $arr = array_merge($arr, self::_items($fullpath, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles));
                    }
                }
            }
        }
    
        closedir($handle);
    
        return $arr;
    }
}