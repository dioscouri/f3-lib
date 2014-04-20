<?php
namespace Dsc\Mongo\Collections;

class Nested extends \Dsc\Mongo\Collections\Nodes
{
    public $title;
    public $slug;    
    public $is_root;
    public $tree;
    public $parent;
    public $path;
    public $lft;
    public $rgt;
    public $ordering;
    
    protected $__collection_name = 'common.nested_sets';
    protected $__type = 'common.nested_sets';
    protected $__config = array(
        'default_sort' => array(
            'title' => 1
        ),
    );
    
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
    
            $this->setCondition('$or', $where);
        }
    
        $filter_slug = $this->getState('filter.slug');
        if (strlen($filter_slug))
        {
            $this->setCondition('slug', $filter_slug);
        }
    
        $filter_title = $this->getState('filter.title');
        if (strlen($filter_title))
        {
            $this->setCondition('title', $filter_title);
        }
    
        return $this;
    }
    
    protected function beforeValidate()
    {
        if (empty($this->slug) && !empty($this->title))
        {
            $this->slug = $this->generateSlug();
        }
        
        return parent::beforeValidate();
    }
    
    public function validate()
    {
        if (empty($this->title)) {
            $this->setError('Title is required');
        }
        
        if (empty($this->tree))
        {
            $this->setError('Items must be part of a tree');
        }
        
        // is the path unique?
        // this would be a great case for $this->validateWith( $validator ); -- using a Uniqueness Validator
        if ($existing = $this->pathExists( $this->path ))
        {
            if ((empty($this->_id) || $this->_id != $existing->_id) && $existing->type == $this->type)
            {
                $this->setError('An item with this title already exists with this parent.');
            }
        }
    
        return parent::validate();
    }
    
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
    
            while ($this->slugExists($slug))
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
    public function slugExists( $slug )
    {
        $clone = (new static)->load(array('slug'=>$slug, 'type'=>$this->__type));
    
        if (!empty($clone->id)) {
            return $clone;
        }
    
        return false;
    }

    /**
     * 
     * @param string $unique
     * @return string
     */
    public function generatePath( $unique=true )
    {
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug();
        }
        
        $path = null;
        
        if (empty($this->parent) || $this->parent == 'null') 
        {
            if (empty($this->is_root)) 
            {
                if ($root = $this->getRoot( $this->tree )) 
                {
                    $path .= $root->path;
                }
            }
            $path .= "/" . $this->slug;
            
            return $path;
        }
        
        // get the parent's path, append the slug
        $parent = (new static)->load( array('_id' => new \MongoId( (string) $this->parent ) ) );
        if (!empty($parent->path)) {
            $path = $parent->path;
        }
        
        $path .= "/" . $this->slug;
        
        return $path;
    }
    
    /**
     * 
     * @param unknown $path
     * @return unknown|boolean
     */
    public function pathExists( $path )
    {
        $clone = (new static)->load(array('path'=>$path, 'type'=>$this->__type));
    
        if (!empty($clone->id)) {
            return $clone;
        }
    
        return false;
    }    
    
    /**
     * Set a create flag
     * 
     * @param unknown $document
     * @param unknown $options
     */
    protected function beforeCreate()
    {
        $this->__isCreate = true;
    }

    /**
     * beforeCreate is triggered before beforeSave,
     * and we ONLY want this to happen if all validations have passed
     */
    protected function beforeSave()
    {
        // this is an insert
        if (!empty($this->__isCreate)) 
        {
            // allow plugin events to halt operation BEFORE making any changes to collection
            $return = parent::beforeSave();
            
            $parent = new static;
            if (empty( $this->parent ) && empty( $this->is_root ))
            {
                $root = $this->getRoot( $this->tree );
                $this->parent = $root->id;
                	
                $parent->load( array('_id'=> new \MongoId( (string) $this->parent ) ) );
            }
            elseif (!empty($this->parent))
            {
                $parent->load( array('_id'=> new \MongoId( (string) $this->parent ) ) );
            }
            
            if ($parent->hasDescendants())
            {
                $rgt = $parent->rgt;
            
                // UPDATE nested_category SET rgt = rgt + 2 WHERE rgt >= @myRight;
                $result = $this->collection()->update(
                    array(
                        'rgt' => array( '$gte' => $rgt ),
                        'tree' => $this->tree
                    ),
                    array(
                        '$inc' => array( 'rgt' => 2 )
                    ),
                    array(
                        'multiple'=> true
                    )
                );
            
                // UPDATE nested_category SET lft = lft + 2 WHERE lft > @myRight;
                $result = $this->collection()->update(
                    array(
                        'lft' => array( '$gt' => $rgt ),
                        'tree' => $this->tree
                    ),
                    array(
                        '$inc' => array( 'lft' => 2 )
                    ),
                    array(
                        'multiple'=> true
                    )
                );
            
                // INSERT INTO nested_category(name, lft, rgt) VALUES('GAME CONSOLES', @myRight, @myRight + 1);
                $this->lft = $rgt;
                $this->rgt = $rgt + 1;
            }
            elseif (!empty($parent->lft))
            {
                // SELECT @myLeft := lft FROM nested_category
                $lft = $parent->lft;
            
                // UPDATE nested_category SET rgt = rgt + 2 WHERE rgt > @myLeft;
                $result = $this->collection()->update(
                    array(
                        'rgt' => array( '$gt' => $lft ),
                        'tree' => $this->tree
                    ),
                    array(
                        '$inc' => array( 'rgt' => 2 )
                    ),
                    array(
                        'multiple'=> true
                    )
                );
            
                // UPDATE nested_category SET lft = lft + 2 WHERE lft > @myLeft;
                $result = $this->collection()->update(
                    array(
                        'lft' => array( '$gt' => $lft ),
                        'tree' => $this->tree
                    ),
                    array(
                        '$inc' => array( 'lft' => 2 )
                    ),
                    array(
                        'multiple'=> true
                    )
                );
            
                // INSERT INTO nested_category(name, lft, rgt) VALUES('FRS', @myLeft + 1, @myLeft + 2);
                $this->lft = $lft + 1;
                $this->rgt = $lft + 2;
            }
            else
            {
                $this->lft = 1;
                $this->rgt = 2;
            }        	
        }
        
        // just a normal overwrite/update
        else {
        	
            $return = parent::beforeSave();
            
        }
        
        return $return;
    }

    protected function beforeUpdate()
    {
        // are we moving the node? or just updating its details?
        $this->__isMoving = false;
        $this->__oldNode = clone $this;
        $this->__oldNode->load( array( '_id' => $this->id ) );
        if ($this->__oldNode->parent != $this->parent)
        {
            $this->__isMoving = true;
        }
        
        return parent::beforeUpdate();
    }
    
    protected function afterUpdate()
    {        
        if (!empty($this->__isMoving))
        {
            $this->rebuildTree( $this->tree );
            
            // if we just removed a leaf/branch to a new tree, rebuild the old tree too 
            if (!empty($this->__oldNode) && $this->tree != $this->__oldNode->tree) 
            {
                $this->rebuildTree( $this->__oldNode->tree );
            }
        }
        
        return parent::afterUpdate();
    }
    
    public function remove() 
    {
        $this->beforeDelete();
        
        // DELETE FROM nested_category WHERE lft BETWEEN @myLeft AND @myRight; 
        $this->__last_operation = $this->collection()->remove(
                array(
                    'lft' => array('$gte' => $this->lft ),
                    'rgt' => array('$lte' => $this->rgt ),
                    'tree' => $this->tree
                )
        );
        
        // THE FOLLOWING IS AN ALTERNATVE TO THE ABOVE -- any advantages?
            /*
            // Delete the children        
            $this->deleteDescendants( $this );
            // Then erase this one too
            $eraseThis = parent::erase($filter);
            */
        
        // UPDATE nested_category SET rgt = rgt - @myWidth WHERE rgt > @myRight;
        // $this->_width = (int) ($this->rgt - $this->lft + 1);
        $width = (int) ($this->rgt - $this->lft + 1);
        
        $this->__last_operation = $this->collection()->update(
                array(
                    'rgt' => array( '$gt' => $this->rgt ),
                    'tree' => $this->tree
                ),
                array(
                    '$inc' => array( 'rgt' => -$width )
                ),
                array(
                    'multiple'=> true
                )
        );
        
        // UPDATE nested_category SET lft = lft - @myWidth WHERE lft > @myRight;
        $this->__last_operation = $this->collection()->update(
                array(
                    'lft' => array( '$gt' => $this->rgt ),
                    'tree' => $this->tree
                ),
                array(
                    '$inc' => array( 'lft' => -$width )
                ),
                array(
                    'multiple'=> true
                )
        );
        
        $this->afterDelete();
        
        return $this->lastOperation();
    }
        
    /**
     * Determines whether item has descendants
     * @return int|boolean
     */
    public function hasDescendants()
    {
        $descendants = ($this->rgt - $this->lft - 1) / 2;
        if (intval($descendants) > 0)
        {
            return $descendants;
        }
        
        return false;
    }
    
    public function parent()
    {
        $parent = (new static)->load( array('_id' => new \MongoId( (string) $this->parent ) ) );
        if (!empty($parent->id)) {
            return $parent;
        }
        
        return false;
    }
    
    public function getChildren( $mapper )
    {
        $filter = array(
        	'parent' => $mapper->id
        );
        
        $this->cursor = $this->collection()->find( $filter, array() );
        $this->cursor = $this->cursor->sort(array('lft' => 1));
        
        $result = array();
        while ($this->cursor->hasnext()) {
            $result[] = $this->cursor->getnext();
        }
        
        $out = array();
        foreach ($result as $doc) {
            $out[] = $this->factory($doc);
        }
            
        return $out;
    }
    
    public function getDescendants( $mapper )
    {
        $filter = array(
            'lft' => array('$gte' => $mapper->lft ),
            'rgt' => array('$lte' => $mapper->rgt ),
            'tree' => $mapper->tree
        );
    
        $this->cursor = $this->collection()->find( $filter, array() );
        $this->cursor = $this->cursor->sort(array('lft' => 1));
    
        $result = array();
        while ($this->cursor->hasnext()) {
            $result[] = $this->cursor->getnext();
        }
    
        $out = array();
        foreach ($result as $doc) {
            $out[] = $this->factory($doc);
        }
    
        return $out;
    }
    
    public function deleteDescendants( $mapper )
    {
        $result = $this->collection()->remove(
        	array( 
                'lft' => array('$gt' => $mapper->lft ),
                'rgt' => array('$lt' => $mapper->rgt ),
                'tree' => $mapper->tree
            )
        );
            
        return $result;
    }
    
    public static function getRoot( $tree ) 
    {
        $root = new static;
        $root->load(array(
        	'tree' => new \MongoId((string) $tree),
            'is_root' => true
        ));
        
        if (!empty($root->id)) {
            return $root;
        }
        
        return false;        
    }
    
    public function rebuildTree( $tree, $node=null, $left=1 ) 
    {
        if ($node === null)
        {
            $node = $this->getRoot( $tree );
        }
        
        // the right value of this node is the left value + 1
        $right = $left + 1;
        
        // get all children of this node
        if ($children = $this->getChildren( $node )) 
        {
            foreach ($children as $child) 
            {
                // recursive execution of this function for each
                // child of this node
                // $right is the current right value, which is
                // incremented by the rebuildTree function
                $right = $this->rebuildTree( $node->tree, $child, $right );
            }
        }
        
        // we've got the left value, and now that we've processed
        // the children of this node we also know the right value
        $node->lft = $left;
        $node->rgt = $right;
        $node->save();
        
        // return the right value of this node + 1
        $return = $right + 1;
        
        return $return;
    }
    
    /**
     * Move a node one position to the left in the same level of the tree
     */
    public function moveUp() 
    {
        // Get the sibling immediately to the left of this node
        $sibling = clone $this;
        $sibling->reset();
        $sibling->load(array('tree' => $this->tree, 'rgt' => $this->lft - 1 ));

        // fail of no sibling found
        if (empty($sibling->id)) {
            return false;
        }
        
        $ids = array();
        // Get the primary keys of descendant nodes, including this node's
        if ($descendants = $this->getDescendants( $this )) {
            $ids = \Joomla\Utilities\ArrayHelper::getColumn( $descendants, '_id' );
        }

        $width = (int) ($this->rgt - $this->lft + 1);
        $sibling_width = (int) ($sibling->rgt - $sibling->lft + 1);
        
        // Shift left and right values for the node and its children.
        $result = $this->collection()->update(
                array(
                    'lft' => array('$gte' => $this->lft, '$lte' => $this->rgt ),
                    'tree' => $this->tree
                ),
                array(
                    '$inc' => array( 'lft' => -$sibling_width, 'rgt' => -$sibling_width )
                ),
                array(
                    'multiple'=> true
                )
        );

        // Shift left and right values for the sibling and its children 
        // explicitly excluding the node's descendants 
        $result = $this->collection()->update(
                array(
                    '_id' => array('$nin' => $ids ),
                    'lft' => array('$gte' => $sibling->lft, '$lte' => $sibling->rgt ),
                    'tree' => $this->tree
                ),
                array(
                    '$inc' => array( 'lft' => $width, 'rgt' => $width )
                ),
                array(
                    'multiple'=> true
                )
        );        

        return $result;
    }
    
    /**
     * Move a node one position to the right in the same level of the tree
     */
    public function moveDown()
    {
        // Get the sibling immediately to the left of this node
        $sibling = clone $this;
        $sibling->reset();
        $sibling->load(array('tree' => $this->tree, 'lft' => $this->rgt + 1 ));
    
        // fail of no sibling found
        if (empty($sibling->id)) {
            return false;
        }
    
        $ids = array();
        // Get the primary keys of descendant nodes, including this node's
        if ($descendants = $this->getDescendants( $this )) {
            $ids = \Joomla\Utilities\ArrayHelper::getColumn( $descendants, '_id' );
        }
    
        $width = (int) ($this->rgt - $this->lft + 1);
        $sibling_width = (int) ($sibling->rgt - $sibling->lft + 1);
    
        // Shift left and right values for the node and its children.
        $result = $this->collection()->update(
                array(
                    'lft' => array('$gte' => $this->lft, '$lte' => $this->rgt ),
                    'tree' => $this->tree
                ),
                array(
                    '$inc' => array( 'lft' => $sibling_width, 'rgt' => $sibling_width )
                ),
                array(
                    'multiple'=> true
                )
        );
    
        // Shift left and right values for the sibling and its children
        // explicitly excluding the node's descendants
        $result = $this->collection()->update(
                array(
                    '_id' => array('$nin' => $ids ),
                    'lft' => array('$gte' => $sibling->lft, '$lte' => $sibling->rgt ),
                    'tree' => $this->tree
                ),
                array(
                    '$inc' => array( 'lft' => -$width, 'rgt' => -$width )
                ),
                array(
                    'multiple'=> true
                )
        );
    
        return $result;
    }
    
    public function getDepth( &$mapper=null ) 
    {
        if (empty($mapper)) 
        {
            $mapper = &$this;
        }
        
        if (!isset($mapper->depth)) 
        {
            $mapper->depth = substr_count( $mapper->path, "/" );
        }
        
        return $mapper->depth;
    }
    
    public static function rebuildTrees($options=array())
    {
        if (!empty($options['trees']) && is_array($options['trees'])) {
            foreach ($options['trees'] as $tree)
            {
                $model = new static;
                $model = $model->rebuildTree( $tree );
            }
        }
        
        return true;
    }
}
?>