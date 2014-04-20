<?php 
namespace Dsc\Mongo\Collections;

class Assets extends \Dsc\Mongo\Collections\Describable 
{
    protected $__collection_name = 'common.assets.files';
    protected $__collection_name_gridfs = 'common.assets';
    protected $__type = 'common.assets';
    protected $__config = array(
        'default_sort' => array(
            'metadata.created.time' => 1
        ),
    );

    /**
     * This is static so you can do
     * YourModel::collection()->find() or anything else with the MongoCollection object
     */
    public static function collectionGridFS()
    {
        $item = new static();
        return $item->getDb()->selectCollection( $item->collectionNameGridFS() );
    }
    
    /**
     * Gets the collection name for this model
     */
    public function collectionNameGridFS()
    {
        if (empty($this->__collection_name_gridfs))
        {
            throw new \Exception('Must specify a collection name for GridFS');
        }
    
        return $this->__collection_name_gridfs;
    }    
    
    /**
     * 
     * @param string $unique
     * @return string
     */
    public function generateSlug( $unique=true )
    {
        if (empty($this->title)) {
            $this->setError('A title is required for generating the slug');
            return $this->checkErrors();
        }
    
        
        if (!empty($this->{'metadata.created.time'})) {
            $created = date('Y-m-d', $this->{'metadata.created.time'});
        } else {
            $created = date('Y-m-d');
        }
        
        $slug = \Web::instance()->slug( $created . '-' . $this->title );
    
        if ($unique)
        {
            $base_slug = $slug;
            $n = 1;
            while ($this->slugExists($slug))
            {
                $now = microtime(true);
                $suffix = md5( $now . "." . $n );
                $slug = $base_slug . '-' . $suffix;
                $n++;
            }
        }
    
        return $slug;
    }
    
    /**
     * 
     * @param unknown $buffer
     */
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
    
    /**
     * 
     * @param unknown $imagick
     * @param unknown $options
     */
    public function getThumbFromImagick( $imagick, $options=array() )
    {
        $width = !empty($options['width']) ? (int) $options['width'] : 460;
        $height = !empty($options['height']) ? (int) $options['height'] : 308;
        
        $imagick->setImageFormat("jpeg");
        $imagick->thumbnailImage($width, $height, true);
        return $imagick->getImageBlob();
    }
    
    /**
     * 
     * @param unknown $buffer
     * @return boolean
     */
    public function isBlobImage( $buffer )
    {
        try {
            $this->imagick = new \Imagick();
            return $this->imagick->readimageblob($buffer);
        } catch (\Exception $e) {
            return false;
        } 
    }
    
    /**
     * 
     * @param unknown $buffer
     * @param string $ext
     * @param unknown $options
     * @return NULL
     */
    public function getThumb( $buffer, $ext=null, $options=array() )
    {
        if ($this->isBlobImage( $buffer )) {
            return $this->getThumbFromImagick( $this->imagick, $options );
        } elseif ($ext) {
            return $this->getThumbFromExtension( $ext, $options );
        }
        
        return null;
    }
    
    /**
     * Creates an asset directly from a URL
     * 
     * @param unknown $url
     * @param unknown $options
     * @return multitype:string NULL boolean
     */
    public static function createFromUrl( $url, $options=array() )
    {
        $options = $options + array('width'=>460, 'height'=>308);
        
        $app = \Base::instance();
        $web = \Web::instance();
    
        $request = array();
        $result = array();
    
        $request = $web->request( $url );
        if (!empty($request['body']))
        {
            $model = new static;
            $db = $model->getDb();
            $grid = $db->getGridFS( $model->collectionNameGridFS() );

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
                $model->load(array('_id'=>$storedfile));
                $model->bind($values);
                $model->save();
            }

            // $storedfile has newly stored file's Document ID
            $result["asset_id"] = (string) $storedfile;
            $result["slug"] = $model->{'metadata.slug'};
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