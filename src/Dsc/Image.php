<?php 
namespace Dsc;

class Image extends \Joomla\Image\Image 
{
    public function getHandle() 
    {
        return $this->handle;
    }
    
    public function toBuffer( $type = IMAGETYPE_JPEG, array $options = array() )
    {
        // Make sure the resource handle is valid.
        if (!$this->isLoaded())
        {
            throw new \LogicException('No valid image was loaded.');
        }
                
        ob_start();
        switch ($type)
        {
            case IMAGETYPE_GIF:
                imagegif($this->handle);
                break;
        
            case IMAGETYPE_PNG:
                imagepng($this->handle, null, (array_key_exists('quality', $options)) ? $options['quality'] : 0);
                break;
        
            case IMAGETYPE_JPEG:
            default:
                imagejpeg($this->handle, null, (array_key_exists('quality', $options)) ? $options['quality'] : 100);
        }        
        $result = ob_get_contents();
        ob_end_clean();
        
        return $result;
    }
    
    public static function dataUri($binary, $mime='image/jpeg')
    {
        $base64 = base64_encode($binary);
        return ('data:' . $mime . ';base64,' . $base64);
    }
    
    
    public function section($imgInitW, $imgInitH,$imgW,$imgH,$imgX1, $imgY1, $cropW, $cropH) {
    	
    	// Make sure the resource handle is valid.
    	if (!$this->isLoaded())
    	{
    		throw new \LogicException('No valid image was loaded.');
    	}
    	
    	
    	// Create the new truecolor image handle. to hold the section
    	$resizedImage = imagecreatetruecolor($imgW, $imgH);
    	imagecopyresampled($resizedImage, $this->handle, 0, 0, 0, 0, $imgW,
    	$imgH, $imgInitW, $imgInitH);
    	
    	//place the data from the section in the newly created image
    	$handle = imagecreatetruecolor($cropW, $cropH);
    	imagecopyresampled($handle, $resizedImage, 0, 0, $imgX1, $imgY1, $cropW,
    	$cropH, $cropW, $cropH);
    	
    	
    	
    	// @codeCoverageIgnoreStart
    	$new = new static($handle);
    	 
    	return $new;
    	
      	
    	
    }
    
}
?>