<?php 
namespace Dsc\Models;

class Assets extends Nodes 
{
    protected $collection = 'common.assets.files';
    protected $collection_gridfs = 'common.assets';
    protected $type = 'common.assets';

    public function getMapper()
    {
        $mapper = null;
        if ($this->collection) {
            $mapper = new \Dsc\Mongo\Mappers\Asset( $this->getDb(), $this->getCollectionName() );
        }
        return $mapper;
    }
    
    public function getGridFSCollectionName() 
    {
        return $this->collection_gridfs;
    }
    
    public function generateSlug( $values, $mapper=null, $unique=true )
    {
        if (empty($values['metadata']['title'])) {
            $this->setError('Title is required');
        }
        $this->checkErrors();
    
        $created = date('Y-m-d');
        if (!empty($values['created']['time'])) {
            $created = date('Y-m-d', $values['created']['time']);
        } elseif (!empty($mapper->created) && !empty($mapper->created['time'])) {
            $created = date('Y-m-d', $mapper->created['time']);
        }
    
        $slug = \Web::instance()->slug( $created . '-' . $values['metadata']['title'] );
    
        if ($unique)
        {
            $base_slug = $slug;
            $n = 1;
            while ($this->slugExists($slug))
            {
                $slug = $base_slug . '-' . $n;
                $n++;
            }
        }
    
        return $slug;
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
    public function getThumbFromExtension( $ext ) 
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
        /*
        $gd_resource = imagecreatefromstring($buffer);
        */
        
        $image = new \Dsc\Image( $gd_resource );
        $thumb = $image->resize(64, 64, false);
        return $thumb->toBuffer();
    }
    
    public function getThumbFromImagick( $imagick )
    {
        $imagick->setImageFormat("jpeg");
        $imagick->thumbnailImage(64, 64, true);
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
    
    public function getThumb( $buffer, $ext=null )
    {
        if ($this->isBlobImage( $buffer )) {
            return $this->getThumbFromImagick( $this->imagick );
        } elseif ($ext) {
            return $this->getThumbFromExtension( $ext );
        }
        
        return null;
    }
    
    protected function fetchFilters()
    {
        $this->filters = array();
    
        $filter_keyword = $this->getState('filter.keyword');
        if ($filter_keyword && is_string($filter_keyword))
        {
            $key =  new \MongoRegex('/'. $filter_keyword .'/i');
    
            $where = array();
            $where[] = array('metadata.title'=>$key);
            $where[] = array('metadata.creator.name'=>$key);
    
            $this->filters['$or'] = $where;
        }
    
        $filter_id = $this->getState('filter.id');
        if (strlen($filter_id))
        {
            $this->filters['_id'] = new \MongoId((string) $filter_id);
        }
    
        $filter_slug = $this->getState('filter.slug');
        if ($filter_slug) {
            $this->filters['metadata.slug'] = $filter_slug;
        }
        
        $filter_type = $this->getState('filter.type');
        if (strlen($filter_type))
        {
            $key =  new \MongoRegex('/'. $filter_type .'/i');
            $this->filters['contentType'] = $key;
        }
    
        return $this->filters;
    }
    
    public function save( $values, $options=array(), $mapper=null )
    {
        if (empty($values['metadata']['slug']))
        {
            if (!empty($mapper->{'metadata.slug'})) {
                $values['metadata']['slug'] = $mapper->{'metadata.slug'};
            }
            else {
                $values['metadata']['slug'] = $this->generateSlug( $values, $mapper );
            }            
        }
        
        if (empty($values['md5']))
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
    
        if (!empty($values['metadata']['tags']) && !is_array($values['metadata']['tags']))
        {
            $values['metadata']['tags'] = trim($values['metadata']['tags']);
            if (!empty($values['metadata']['tags'])) {
                $values['metadata']['tags'] = \Base::instance()->split( (string) $values['metadata']['tags'] );
            }
        }
    
        if (empty($values['metadata']['tags'])) {
            unset($values['metadata']['tags']);
        }
    
        return parent::save( $values, $options, $mapper );
    }
}