<?php 
namespace Dsc\Mongo\Collections;

class Assets extends \Dsc\Mongo\Collections\Describable 
{
    public $thumb = null;       // binary data
    public $url = null;         // path to asset
    public $uploadDate = null;  // MongoDate
    public $source_url = null;  // URL where file originally was (only used on URL uploads)  
    public $filename = null;    // filename of original file
    public $s3 = array();       // the object's s3 info values
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
        $quality = !empty($options['quality']) ? (int) $options['quality'] : 75;
        
        $imagick->setImageFormat("jpeg");
        $imagick->setImageCompressionQuality($quality);
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
    		$where[] = array('md5'=>$key);
    		
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
			if (!empty($this->{'s3.ETag'})) {
                $this->md5 = str_replace('"', '', $this->{'s3.ETag'} );
            }
            elseif (!empty($this->filename) && file_exists($this->filename)) {
                $this->md5 = md5_file( $this->filename );
            }            
            else {
                $this->md5 = md5( $this->slug );
            }
        }
    	
        return parent::beforeSave();
    }
    
    /**
     * Creates an asset directly from an uploaded file
     *
     * @param array $file_upload, PHP array from $_FILES
     * @param array $options
     * 
     * @throws \Exception
     * @return \Dsc\Mongo\Collections\Assets
     */
    public static function createFromUpload( array $file_upload, $options=array() )
    {
        if (!isset($file_upload['error']) || is_array($file_upload['error'])) 
        {
            throw new \Exception('Invalid Upload');
        }
        
        switch ($file_upload['error']) 
        {
        	case UPLOAD_ERR_OK:
        	    break;
        	case UPLOAD_ERR_NO_FILE:
        	    throw new \Exception('No file sent.');
        	case UPLOAD_ERR_INI_SIZE:
        	case UPLOAD_ERR_FORM_SIZE:
        	    throw new \Exception('Exceeded filesize limit.');
        	default:
        	    throw new \Exception('Unknown errors.');
        }
        
        if (empty($file_upload['tmp_name']) || empty($file_upload['name']))
        {
            throw new \Exception('Invalid Upload Properties');
        }

        if (empty($file_upload['size']))
        {
            throw new \Exception('Invalid Upload Size');
        }
        
        $app = \Base::instance();
        $options = $options + array('width'=>460, 'height'=>308);
        
        // Do the upload        
        $model = new static;
        $grid = $model->getDb()->getGridFS( $model->collectionNameGridFS() );
        $file_path = $model->inputFilter()->clean($file_upload['tmp_name']);
        $name = $model->inputFilter()->clean($file_upload['name']);
        
        $values = array(
            'type' => !empty($options['type']) ? $options['type'] : null,
            'storage' => 'gridfs',
            'md5' => md5_file( $file_path ),
            'url' => null,
            "title" => \Joomla\String\Normalise::toSpaceSeparated( $name ),
        );
        
        if ($storedfile = $grid->storeFile( $file_path, $values ))
        {
            $model->load(array('_id'=>$storedfile));
            $model->bind( $values );
            $model->slug = $model->generateSlug();
            $model->save();
        }
        
        return $model;
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
                'title' => $title,
                "filename" => $filename,
                "source_url" => $url,
            );

            $model->bind($values);
            
            $values['slug'] = $model->generateSlug();
            $values['url'] = "/asset/" . $values['slug'];
            
            // save the file
            if ($storedfile = $grid->storeBytes( $buffer, $values ))
            {
                $model->load(array('_id'=>$storedfile));
                $model->bind($values);
                if ($model->save()) 
                {
                    
                }
            }

            // $storedfile has newly stored file's Document ID
            $result["asset_id"] = (string) $storedfile;
            $result["slug"] = $model->{'slug'};
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
     * Creates an asset directly from a URL
     * and send it directly to S3
     * 
     * @param string $url
     * @param array $options
     * @throws \Exception
     */
    public static function createFromUrlToS3( $url, $options=array() )
    {
        $app = \Base::instance();
         
        $s3_options = array(
            'clientPrivateKey' => $app->get('aws.clientPrivateKey'),
            'serverPublicKey' => $app->get('aws.serverPublicKey'),
            'serverPrivateKey' => $app->get('aws.serverPrivateKey'),
            'expectedBucketName' => $app->get('aws.bucketname'),
            'expectedMaxSize' => $app->get('aws.maxsize'),
            'cors_origin' => $app->get('SCHEME') . "://" . $app->get('HOST') . $app->get('BASE')
        );
         
        if (!class_exists('\Aws\S3\S3Client')
        || empty($s3_options['clientPrivateKey'])
        || empty($s3_options['serverPublicKey'])
        || empty($s3_options['serverPrivateKey'])
        || empty($s3_options['expectedBucketName'])
        || empty($s3_options['expectedMaxSize'])
        )
        {
            throw new \Exception('Invalid configuration settings');
        }
                
        $options = $options + array('width'=>460, 'height'=>308);
    
        $request = \Web::instance()->request( $url );
        if (empty($request['body'])) {
            throw new \Exception('Could not download asset from provided URL');
        }
        
        $model = new static;
        
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
            'storage' => 's3',
            'contentType' => $model->getMimeType( $buffer ),
            'md5' => md5( $filename ),
            'thumb' => $thumb,
            'url' => null,
            "source_url" => $url,
            "filename" => $filename,
            'title' => $title,
        );
        
        $model->bind($values);
        
        // these need to happen after the bind
        $model->slug = $model->generateSlug();
        $model->_id = new \MongoId;
        
        /**
         * Push to S3
         */
        $bucket = $app->get( 'aws.bucketname' );
        $s3 = \Aws\S3\S3Client::factory(array(
            'key' => $app->get('aws.serverPublicKey'),
            'secret' => $app->get('aws.serverPrivateKey')
        ));
        
        $key = (string) $model->_id;
        
        $res = $s3->putObject(array(
            'Bucket' => $bucket,
            'Key' => $key,
            'Body' => $buffer,
            'ContentType' => $model->contentType,
        ));
         
        $s3->waitUntil('ObjectExists', array(
            'Bucket' => $bucket,
            'Key'    => $key
        ));
         
        if( !$s3->doesObjectExist( $bucket, $key ) ){
            throw new \Exception( "Upload to Amazon S3 failed" );
        }
         
        $objectInfoValues = $s3->headObject(array(
            'Bucket' => $bucket,
            'Key' => $key
        ))->getAll();
        
        /**
         * END Push to S3
        */
        
        $model->url = $s3->getObjectUrl($bucket, $key);
         
        $model->s3 = array_merge( array(), (array) $model->s3, array(
            'bucket' => $bucket,
            'key' => $key,
            'uuid' => (string) $model->_id
        ) )  + $objectInfoValues;
        
        return $model->save();
    }    
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctTypes($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("type", $query);
        $distinct = array_values( array_filter( $distinct ) );
    
        return $distinct;
    }
    
    /**
     *
     * @param array $types
     * @return unknown
     */
    public static function distinctStores($query=array())
    {
        $model = new static();
        $distinct = $model->collection()->distinct("storage", $query);
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

    	$pathinfo = pathinfo($this->{'filename'});
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
    	
    	$this->s3 = array_merge( array(), (array) $this->s3, array(
    		'bucket' => $bucket,
    		'key' => $key,
    		'filename' => $pathinfo['basename'],
    		'uuid' => (string)$this->id
    	) )  + $objectInfoValues;
    	
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
    
    /**
     * Check if a URL is valid
     * 
     * @param string $url
     * @return boolean
     */
    public static function checkUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);         // only make a HEAD request
        curl_setopt($ch, CURLOPT_FAILONERROR, true);    // fail if response >= 400
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if (curl_exec($ch) !== false)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Gets the binary data for the requested asset's thumbnail,
     * first looking for the cached filesystem version,
     * then loading the asset (throwing an exception if invalid)
     * and recreating the filesystem version
     * 
     * @param unknown $slug
     * @param bool $refresh
     * 
     * @throws \Exception
     * 
     * @return array
     */
    public static function cachedThumb($slug, $refresh=false)
    {
        $binary_data = null;
        $last_modified = null;
        $md5 = null;
        
        $app = \Base::instance();
        $files_path = $app->get('TEMP') . "assets_thumbs";
        if (!file_exists($files_path)) {
            mkdir( $files_path, \Base::MODE, true );
        }
        
        $file_path = $files_path . '/' . $slug;
        
        if (!file_exists($file_path) || $refresh) 
        {
            $item = (new static)->setState('filter.slug', $slug)->getItem();
            if (empty($item->id))
            {
                throw new \Exception( 'Invalid Item' );
            }            
            
            $binary_data = $item->thumb->bin;
            $last_modified = (int) $item->{'metadata.last_modified.time'};
            $md5 = $item->md5;
                        
            file_put_contents( $file_path, $binary_data );
        }
        else 
        {
            $binary_data = file_get_contents( $file_path );
            $last_modified = filemtime( $file_path );
            $md5 = md5_file( $file_path );            
        }
        
        return array(
        	'bin' => $binary_data,
            'last_modified' => $last_modified,
            'md5' => $md5,
        );
    }
    
    public function rebuildThumb()
    {
        // Get the full image's binary data -- whether from S3 or GridFS
        switch ($this->storage)
        {
        	case "s3":
        
        	    $request = \Web::instance()->request( $this->url );
        	    if (empty($request['body'])) {
        	        throw new \Exception('Invalid Item URL');
        	    }
        
        	    $buffer = $request['body'];
        
        	    break;
        
        	case "gridfs":
        
        	    $db = $this->getDb();
        	    $gridfs = $db->getGridFS( $this->collectionNameGridFS() );
        	    $length = $this->{"length"};
        	    $chunkSize = $this->{"chunkSize"};
        	    $chunks = ceil( $length / $chunkSize );
        	    $collChunkName = $this->collectionNameGridFS() . ".chunks";
        	    $collChunks = $this->getDb()->{$collChunkName};
        	    	
        	    $buffer = null;
        	    for( $i=0; $i<$chunks; $i++ )
        	    {
        	        $chunk = $collChunks->findOne( array( "files_id" => $this->id, "n" => $i ) );
        	        $buffer .= (string) $chunk["data"]->bin;
        	    }
        
        	    break;
        
        	default:

        	    throw new \Exception('Invalid Item Storage');
        	     
        	    break;
        }

        if ( $thumb_binary_data = $this->getThumb( $buffer )) {
            $thumb = new \MongoBinData( $thumb_binary_data, 2 );
        }
        
        $app = \Base::instance();
        $files_path = $app->get('TEMP') . "assets_thumbs";
        if (!file_exists($files_path)) {
            mkdir( $files_path, \Base::MODE, true );
        }
        $file_path = $files_path . '/' . $this->slug;
        file_put_contents( $file_path, $thumb_binary_data );
        
        return $this->set( 'thumb', $thumb )->save();
    }
}