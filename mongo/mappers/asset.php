<?php
namespace Dsc\Mongo\Mappers;

class Asset extends \Dsc\Mongo\Mapper 
{
    public function isImage( $contentType=null ) 
    {
        if (empty($contentType)) {
            $contentType = $this->contentType;
        }
        
        if (empty($contentType)) 
        {
            return false;
        }
        
        if (substr(strtolower($contentType), 0, 5) == "image") {
            return true;
        }
        
        return false;
    }
}
?>