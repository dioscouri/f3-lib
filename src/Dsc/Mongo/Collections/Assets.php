<?php 
namespace Dsc\Mongo\Collections;

class Assets extends \Dsc\Mongo\Collections\Describable 
{
    public $thumb = null;       // binary data
    public $url = null;         // path to asset
    public $uploadDate = null;  // MongoDate
    public $details = array();
    public $md5 = null;
    public $contentType = null;     // e.g. image/jpeg
    
    protected $__collection_name = 'common.assets.files';
    protected $__collection_name_gridfs = 'common.assets';
    protected $__type = 'common.assets';
    protected $__config = array(
        'default_sort' => array(
            'metadata.created.time' => -1
        ),
    );

    /**
     * This is static so you can do
     * YourModel::collection()->find() or anything else with the MongoCollection object
     */
    public static function collectionGridFS()
    {
        $item = new static;
        return $item->getDb()->getGridFS( $item->collectionNameGridFS() );
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
        $title = !empty($this->get('title')) ? $this->get('title') : $this->{'metadata.title'};
        if (empty($title)) {
            $this->setError('A title is required for generating the slug');
            return $this->checkErrors();
        }
        
        if (!empty($this->{'metadata.created.time'})) {
            $created = date('Y-m-d', $this->{'metadata.created.time'});
        } else {
            $created = date('Y-m-d');
        }
        
        $slug = \Web::instance()->slug( $created . '-' . $title );
    
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

    protected function fetchConditions()
    {
    	parent::fetchConditions();
    
    	$filter_keyword = $this->getState('filter.keyword');
    	if ($filter_keyword && is_string($filter_keyword))
    	{
    		$key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
    		$where = array();
    		$where[] = array('title'=>$key);
    		$where[] = array('slug'=>$key);
    		$where[] = array('metadata.creator.name'=>$key);
    
    		$this->setCondition('$or', $where);
    	}
    	
    	$filter_content_type = $this->getState('filter.content_type');
    	if (strlen($filter_content_type))
    	{
    		$key =  new \MongoRegex('/'. $filter_content_type .'/i');
    		$this->setCondition( 'contentType', $key );
    	}
    	 
        $filter_storage = $this->getState('filter.storage');
    	if (strlen($filter_storage))
    	{
    		$this->setCondition( 'storage', $filter_storage );
    	}
    	
    	return $this;
    }
    
    protected function beforeSave()
    {
    	if (empty($this->type)) {
    		$this->type = $this->__type;
    	}
        
        if (empty($this->md5 ) )
        {
			if (!empty($this->{'details.ETag'})) {
                $this->md5 = str_replace('"', '', $this->{'details.ETag'} );
            }
            elseif (!empty($this->filename)) {
                $this->md5 = md5_file( $this->filename );
            }            
            else {
                $this->md5 = md5( $this->slug );
            }
        }
    	
        return parent::beforeSave();
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
            $filename = $model->inputfilter()->clean( $url_path );
            $buffer = $request['body'];
            $originalname = str_replace( "/", "-", $filename );

            $thumb = null;
            if ( $thumb_binary_data = $model->getThumb( $buffer, null, $options )) {
                $thumb = new \MongoBinData( $thumb_binary_data, 2 );
            }

            $title = \Joomla\String\Normalise::toSpaceSeparated( $model->inputFilter()->clean( $originalname ) );
            
            $values = array(
                'storage' => 'gridfs',
                'contentType' => $model->getMimeType( $buffer ),
                'md5' => md5( $filename ),
                'thumb' => $thumb,
                'url' => null,
                'metadata' => array(
                    "title" => $title
                ),
                'title' => $title,
                'details' => array(
                    "filename" => $filename,
                    "source_url" => $url
                )
            );

            if (empty($values['metadata']['title'])) {
                $values['metadata']['title'] = $values['md5'];
            }

            $model->bind($values);
            
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
        $distinct = $model->collection()->distinct("metadata.type", $query);
        $distinct = array_values( array_filter( $distinct ) );
    
        return $distinct;
    }

    /**
     * Checks if this asset is an image
     * 
     * @param string $contentType
     * @return boolean
     */
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
    
    
    /**
     * This method moves this asset to Amazon S3
     */
    public function moveToS3()
    {
    	$app = \Base::instance();
    	
    	$options = array(
    	    'clientPrivateKey' => $app->get('aws.clientPrivateKey'),
    	    'serverPublicKey' => $app->get('aws.serverPublicKey'),
    	    'serverPrivateKey' => $app->get('aws.serverPrivateKey'),
    	    'expectedBucketName' => $app->get('aws.bucketname'),
    	    'expectedMaxSize' => $app->get('aws.maxsize'),
    	    'cors_origin' => $app->get('SCHEME') . "://" . $app->get('HOST') . $app->get('BASE')
    	);
    	
    	if (!class_exists('\Aws\S3\S3Client')
    	|| empty($options['clientPrivateKey'])
    	|| empty($options['serverPublicKey'])
    	|| empty($options['serverPrivateKey'])
    	|| empty($options['expectedBucketName'])
    	|| empty($options['expectedMaxSize'])
    	)
    	{
    	    throw new \Exception('Invalid configuration settings');
    	}
    	
    	$bucket = $app->get( 'aws.bucketname' );
    	$s3 = \Aws\S3\S3Client::factory(array(
            'key' => $app->get('aws.serverPublicKey'),
            'secret' => $app->get('aws.serverPrivateKey')
        ));

    	$pathinfo = pathinfo($this->{'details.filename'});
    	$key = (string) $this->id;
    	if (!empty($pathinfo['extension'])) {
    	    $key .= '.' . $pathinfo['extension'];
    	}
    	
    	$idx = 1;
    	while ($s3->doesObjectExist( $bucket, $key ) )
    	{
    		$key = (string) $this->id . '-' . $idx;
    		if (!empty($pathinfo['extension'])) {
    		    $key .= '.' . $pathinfo['extension'];
    		}
    		$idx++;
    	}
    	
    	$chunks = ceil( $this->length / $this->chunkSize );
    	
    	$collChunkName = $this->collectionNameGridFS() . ".chunks";
    	$collChunks = $this->getDb()->{$collChunkName};
    	$data = '';
    	for( $i=0; $i<$chunks; $i++ )
    	{
    		$chunk = $collChunks->findOne( array( "files_id" => $this->id, "n" => $i ) );
    		$data .= (string) $chunk["data"]->bin;
    	}
    	 
    	$res = $s3->putObject(array(
    		'Bucket' => $bucket,
    		'Key' => $key,
    		'Body' => $data,
   			'ContentType' => $this->contentType,
    	));
    	
    	$s3->waitUntil('ObjectExists', array(
			'Bucket' => $bucket,
			'Key'    => $key
    	));
    	
    	if( !$s3->doesObjectExist( $bucket, $key ) ){
    		throw new \Exception( "Upload to Amazon S3 failed! - Asset #".(string)$this->id );
    	}
    	
    	$objectInfoValues = $s3->headObject(array(
            'Bucket' => $bucket,
            'Key' => $key
    	))->getAll();
    	
    	$this->storage = 's3';
    	$this->url = $s3->getObjectUrl($bucket, $key);
    	$this->details = array(
    		'bucket' => $bucket,
    		'key' => $key,
    		'filename' => $pathinfo['basename'],
    		'uuid' => (string)$this->id
    	) + $objectInfoValues;
    	
    	$this->clear( 'chunkSize' );
    	$this->save();
    	
    	// delete all chunks
    	$collChunks->remove( array( "files_id" => $this->id ) );
    	    	
    	return $this;
    }
    
    /**
     * Returns an associative array of object's public properties
     * removing any that begin with a double-underscore (__)
     * 
     * Also removes binary data from cast array
     *
     * @param boolean $public
     *            If true, returns only the public properties.
     *
     * @return array
     */
    public function castBinarySafe( $public = true )
    {
        $vars = get_object_vars( $this );
        if ($public)
        {
            foreach ( $vars as $key => $value )
            {
                if (substr( $key, 0, 2 ) == '__' || ! $this->isPublic( $key ))
                {
                    unset( $vars[$key] );
                }
            }
        }
        
        if (!empty($vars['thumb'])) 
        {
            $vars['thumb'] = '<binary>';
        } 
        
        return $vars;
    }
}