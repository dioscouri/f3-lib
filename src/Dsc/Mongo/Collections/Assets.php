<?php 
namespace Dsc\Mongo\Collections;

class Assets extends Taggable 
{
    protected $collection = 'common.assets.files';
    protected $collection_gridfs = 'common.assets';
    protected $type = 'common.assets';
    protected $default_ordering_direction = '-1';
    protected $default_ordering_field = 'metadata.created.time';    

    use \Dsc\Traits\Models\HasSlug;
    
    public function getGridFSCollectionName() 
    {
        return $this->collection_gridfs;
    }
    
    public function getMimeType( $buffer ) 
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($buffer);
    }
    
    /**
     * 
     * @param unknown_type $ext
     * @return binary|NULL
     */
    public function getThumbFromExtension( $ext, $options=array() ) 
    {
        $f3 = \Base::instance();
        $path = $f3->get('PATH_ROOT') . "public/dsc/images/filetypes/" . $ext . ".png";
        if (file_exists($path)) {
            return $this->getThumbFromGDResource( $path );
        }
        return null;
    }
    
    /**
     * 
     * @param unknown_type $gd_resource
     * @return binary
     */
    public function getThumbFromGDResource( $gd_resource )
    {
        $width = !empty($options['width']) ? (int) $options['width'] : 460;
        $height = !empty($options['height']) ? (int) $options['height'] : 308;
                
        /*
        $gd_resource = imagecreatefromstring($buffer);
        */
        
        $image = new \Dsc\Image( $gd_resource );
        $thumb = $image->resize($width, $height, false);
        return $thumb->toBuffer();
    }
    
    public function getThumbFromImagick( $imagick, $options=array() )
    {
        $width = !empty($options['width']) ? (int) $options['width'] : 460;
        $height = !empty($options['height']) ? (int) $options['height'] : 308;
        
        $imagick->setImageFormat("jpeg");
        $imagick->thumbnailImage($width, $height, true);
        return $imagick->getImageBlob();
    }
    
    public function isBlobImage( $buffer )
    {
        try {
            $this->imagick = new \Imagick();
            return $this->imagick->readimageblob($buffer);
        } catch (\Exception $e) {
            return false;
        } 
    }
    
    public function getThumb( $buffer, $ext=null, $options=array() )
    {
        if ($this->isBlobImage( $buffer )) {
            return $this->getThumbFromImagick( $this->imagick, $options );
        } elseif ($ext) {
            return $this->getThumbFromExtension( $ext, $options );
        }
        
        return null;
    }
    
    protected function fetchConditions()
    {
    	parent::fetchConditions();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('metadata.title'=>$key);
            $where[] = array('metadata.creator.name'=>$key);
            $where[] = array('metadata.slug'=>$key);
            
            $this->setCondition('$or', $where);
        }    
    
        $filter_slug = $this->getState('filter.slug');
        if ($filter_slug) {
            $this->setCondition('metadata.slug', $filter_slug );
        }

        $filter_content_type = $this->getState('filter.content_type');
        if (strlen($filter_content_type))
        {
            $key =  new \MongoRegex('/'. $filter_content_type .'/i');
            $this->setCondition('contentType', $key);
        }        
    
        return $this;
    }


    protected function beforeValidate()
    {
    	if (empty($this->slug) && !empty($this->title))
    	{
    		$this->slug = $this->generateSlug();
    	}
    	
    	if (empty( $this->md5 ) )
    	{
    		if (!empty($mapper->{'md5'})) {
    			$values['md5'] = $mapper->{'md5'};
    		}
    		elseif (!empty($mapper->{'details.ETag'})) {
    			$values['md5'] = str_replace('"', '', $mapper->{'details.ETag'} );
    		}
    		elseif (!empty($mapper->{'filename'})) {
    			$values['md5'] = md5_file( $mapper->{'filename'} );
    		}
    		else {
    			$values['md5'] = md5( $values['metadata']['slug'] );
    		}
    	}
    	 
    	return parent::beforeValidate();
    }
    
    protected function beforeSave()
    {
    	if (empty($this->type)) {
    		$this->type = $this->__type;
    	}
    	return parent::beforeSave();
    }
    
    public function createFromUrl( $url, $options=array() )
    {
        $options = $options + array('width'=>460, 'height'=>308);
        
        $app = \Base::instance();
        $web = \Web::instance();
    
        $request = array();
        $result = array();
    
        $request = $web->request( $url );
        if (!empty($request['body']))
        {
            $model = $this;
            $db = $model->getDb();
            $grid = $db->getGridFS( $model->getGridFSCollectionName() );

            $url_path = parse_url( $url , PHP_URL_PATH );
            $pathinfo = pathinfo( $url_path );
            $filename = $this->inputfilter->clean( $url_path );
            $buffer = $request['body'];
            $originalname = str_replace( "/", "-", $filename );

            $thumb = null;
            if ( $thumb_binary_data = $model->getThumb( $buffer, null, $options )) {
                $thumb = new \MongoBinData( $thumb_binary_data, 2 );
            }

            $values = array(
                'storage' => 'gridfs',
                'contentType' => $model->getMimeType( $buffer ),
                'md5' => md5( $filename ),
                'thumb' => $thumb,
                'url' => null,
                'metadata' => array(
                    "title" => \Joomla\String\Normalise::toSpaceSeparated( $this->inputfilter->clean( $originalname ) )
                ),
                'details' => array(
                    "filename" => $filename,
                    "source_url" => $url
                )
            );

            if (empty($values['metadata']['title'])) {
                $values['metadata']['title'] = $values['md5'];
            }

            $values['metadata']['slug'] = $model->generateSlug( $values );
            $values['url'] = "/asset/" . $values['metadata']['slug'];

            // save the file
            if ($storedfile = $grid->storeBytes( $buffer, $values ))
            {
                $mapper = $model->getMapper();
                $mapper->load(array('_id'=>$storedfile));
                $mapper = $model->update( $mapper, $values );
            }

            // $storedfile has newly stored file's Document ID
            $result["asset_id"] = (string) $storedfile;
            $result["slug"] = $mapper->{'metadata.slug'};
            $result['error'] = false;
        } 
            else 
        {
            $result['error'] = true;
            $result['message'] = 'Could not download asset from provided URL';
        }
    
        return $result;
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctTypes($query=array())
    {
        $model = new static();
        $distinct = $model->getCollection()->distinct("metadata.type", $query);
        $distinct = array_values( array_filter( $distinct ) );
    
        return $distinct;
    }
}