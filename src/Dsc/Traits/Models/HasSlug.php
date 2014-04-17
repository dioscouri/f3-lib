<?php
namespace Dsc\Traits\Models;

trait HasSlug
{
    public function generateSlug( $unique=true )
    {
        if (empty($this->title)) {
            $this->setError('A title is required for generating the slug');
            return $this->checkErrors();
        }
    
        $slug = \Web::instance()->slug( $this->title );
    
        if ($unique)
        {
            $base_slug = $slug;
            $n = 1;
            $parent = null;
            if( isset( $this->parent ) && $this->parent != 'null'){
            	$parent = $this->parent;
            }
            
            while ($this->slugExists($slug, $parent))
            {
                $slug = $base_slug . '-' . $n;
                $n++;
            }
        }
    
        return $slug;
    }
    
    /**
     *
     *
     * @param string $slug
     * @return unknown|boolean
     */
    public function slugExists( $slug, $parent = null )
    {
        if (!empty($parent)) 
        {
            $clone = (new static)->load(array('slug'=>$slug, 'type'=>$this->__type, 'parent'=>new \MongoId($parent) ));
        } 
        else 
        {
            $clone = (new static)->load(array('slug'=>$slug, 'type'=>$this->__type));
        }
        
        if (!empty($clone->id)) {
            return $clone;
        }
    
        return false;
    }
}