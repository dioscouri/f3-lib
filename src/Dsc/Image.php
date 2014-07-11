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
    
    
    //REMOVE THESE AFTER JOOMLA IS UPDATED
    
    /**
     * Method to crop the current image.
     *
     * @param   mixed    $width      The width of the image section to crop in pixels or a percentage.
     * @param   mixed    $height     The height of the image section to crop in pixels or a percentage.
     * @param   integer  $left       The number of pixels from the left to start cropping.
     * @param   integer  $top        The number of pixels from the top to start cropping.
     * @param   boolean  $createNew  If true the current image will be cloned, cropped and returned; else
     	*                               the current image will be cropped and returned.
     	*
     	* @return  Image
     	*
     	* @since   1.0
     	* @throws  \LogicException
     	*/
     public function crop($width, $height, $left = null, $top = null, $createNew = true)
     {
     	// Make sure the resource handle is valid.
     	if (!$this->isLoaded())
     	{
     		throw new \LogicException('No valid image was loaded.');
     	}
    
     	// Sanitize width.
     	$width = $this->sanitizeWidth($width, $height);
    
     	// Sanitize height.
     	$height = $this->sanitizeHeight($height, $width);
    
     	// Autocrop offsets
     	if (is_null($left))
     	{
     		$left = round(($this->getWidth() - $width) / 2);
     	}
    
     	if (is_null($top))
     	{
     		$top = round(($this->getHeight() - $height) / 2);
     	}
    
     	// Sanitize left.
     	$left = $this->sanitizeOffset($left);
    
     	// Sanitize top.
     	$top = $this->sanitizeOffset($top);
    
     	// Create the new truecolor image handle.
     	$handle = imagecreatetruecolor($width, $height);
    
     	// Allow transparency for the new image handle.
     	imagealphablending($handle, false);
     	imagesavealpha($handle, true);
    
     	if ($this->isTransparent())
     	{
     		// Get the transparent color values for the current image.
     		$rgba = imageColorsForIndex($this->handle, imagecolortransparent($this->handle));
     		$color = imageColorAllocate($this->handle, $rgba['red'], $rgba['green'], $rgba['blue']);
    
     		// Set the transparent color values for the new image.
     		imagecolortransparent($handle, $color);
     		imagefill($handle, 0, 0, $color);
    
     		imagecopyresized($handle, $this->handle, 0, 0, $left, $top, $width, $height, $width, $height);
     	}
     	else
     	{
     		imagecopyresampled($handle, $this->handle, 0, 0, $left, $top, $width, $height, $width, $height);
     	}
    
     	// If we are cropping to a new image, create a new Image object.
     	if ($createNew)
     	{
     		// @codeCoverageIgnoreStart
     		$new = new static($handle);
    
     		return $new;
    
     		// @codeCoverageIgnoreEnd
     	}
     	else
     	// Swap out the current handle for the new image handle.
     	{
     		// Free the memory from the current handle
     		$this->destroy();
    
     		$this->handle = $handle;
    
     		return $this;
     	}
     }
    
     /**
      * Method to resize the current image.
      *
      * @param   mixed    $width        The width of the resized image in pixels or a percentage.
      * @param   mixed    $height       The height of the resized image in pixels or a percentage.
      * @param   boolean  $createNew    If true the current image will be cloned, resized and returned; else
      	*                                 the current image will be resized and returned.
      	* @param   integer  $scaleMethod  Which method to use for scaling
      	*
      	* @return  Image
      	*
      	* @since   1.0
      	* @throws  \LogicException
      	*/
      public function resize($width, $height, $createNew = true, $scaleMethod = self::SCALE_INSIDE)
      {
      	// Make sure the resource handle is valid.
      	if (!$this->isLoaded())
      	{
      		throw new \LogicException('No valid image was loaded.');
      	}
     
      	// Sanitize width.
      	$width = $this->sanitizeWidth($width, $height);
     
      	// Sanitize height.
      	$height = $this->sanitizeHeight($height, $width);
     
      	// Prepare the dimensions for the resize operation.
      	$dimensions = $this->prepareDimensions($width, $height, $scaleMethod);
     
      	// Instantiate offset.
      	$offset = new \stdClass;
      	$offset->x = $offset->y = 0;
     
      	// Center image if needed and create the new truecolor image handle.
      	if ($scaleMethod == self::SCALE_FIT)
      	{
      		// Get the offsets
      		$offset->x	= round(($width - $dimensions->width) / 2);
      		$offset->y	= round(($height - $dimensions->height) / 2);
     
      		$handle = imagecreatetruecolor($width, $height);
     
      		// Make image transparent, otherwise canvas outside initial image would default to black
      		if (!$this->isTransparent())
      		{
      			$transparency = imagecolorAllocateAlpha($this->handle, 0, 0, 0, 127);
      			imagecolorTransparent($this->handle, $transparency);
      		}
      	}
      	else
      	{
      		$handle = imagecreatetruecolor($dimensions->width, $dimensions->height);
      	}
     
      	// Allow transparency for the new image handle.
      	imagealphablending($handle, false);
      	imagesavealpha($handle, true);
     
      	if ($this->isTransparent())
      	{
      		// Get the transparent color values for the current image.
      		$rgba = imageColorsForIndex($this->handle, imagecolortransparent($this->handle));
      		$color = imageColorAllocateAlpha($this->handle, $rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha']);
     
      		// Set the transparent color values for the new image.
      		imagecolortransparent($handle, $color);
      		imagefill($handle, 0, 0, $color);
      	}
     
      	// Use resampling for better quality
      	imagecopyresampled(
      	$handle, $this->handle,
      	$offset->x, $offset->y, 0, 0, $dimensions->width, $dimensions->height, $this->getWidth(), $this->getHeight()
      	);
     
      	// If we are resizing to a new image, create a new JImage object.
      	if ($createNew)
      	{
      		// @codeCoverageIgnoreStart
      		$new = new static($handle);
     
      		return $new;
     
      		// @codeCoverageIgnoreEnd
      	}
      	else
      	// Swap out the current handle for the new image handle.
      	{
      		// Free the memory from the current handle
      		$this->destroy();
     
      		$this->handle = $handle;
     
      		return $this;
      	}
      }
    
      
      /**
       * Method to rotate the current image.
       *
       * @param   mixed    $angle       The angle of rotation for the image
       * @param   integer  $background  The background color to use when areas are added due to rotation
       * @param   boolean  $createNew   If true the current image will be cloned, rotated and returned; else
       	*                                the current image will be rotated and returned.
       	*
       	* @return  Image
       	*
       	* @since   1.0
       	* @throws  \LogicException
       	*/
       public function rotate($angle, $background = -1, $createNew = true)
       {
       	// Make sure the resource handle is valid.
       	if (!$this->isLoaded())
       	{
       		throw new \LogicException('No valid image was loaded.');
       	}
      
       	// Sanitize input
       	$angle = (float) $angle;
      
       	// Create the new truecolor image handle.
       	$handle = imagecreatetruecolor($this->getWidth(), $this->getHeight());
      
       	// Allow transparency for the new image handle.
       	imagealphablending($handle, false);
       	imagesavealpha($handle, true);
      
       	// Copy the image
       	imagecopy($handle, $this->handle, 0, 0, 0, 0, $this->getWidth(), $this->getHeight());
      
       	// Rotate the image
       	$handle = imagerotate($handle, $angle, $background);
      
       	// If we are resizing to a new image, create a new Image object.
       	if ($createNew)
       	{
       		// @codeCoverageIgnoreStart
       		$new = new static($handle);
      
       		return $new;
      
       		// @codeCoverageIgnoreEnd
       	}
       	else
       	// Swap out the current handle for the new image handle.
       	{
       		// Free the memory from the current handle
       		$this->destroy();
      
       		$this->handle = $handle;
      
       		return $this;
       	}
       }	
    
    
    
}
?>