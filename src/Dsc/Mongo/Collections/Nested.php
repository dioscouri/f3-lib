<?php
namespace Dsc\Mongo\Collections;

class Nested extends \Dsc\Mongo\Collections\Nodes
{
    use \Dsc\Traits\Models\Seo;
    
    public $title;
    public $slug; 
    public $description;
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
            'tree'=> 1, 
            'lft' => 1
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
        
        $filter_root = $this->getState('filter.root');
        if (is_bool($filter_root) && $filter_root)
        {
            $this->setCondition('is_root', true);
        }
        elseif (is_bool($filter_root) && !$filter_root)
        {
            $this->setCondition('is_root', array( '$ne' => true ) );
        }
        
        $filter_tree_slug = $this->getState('filter.tree_slug');
        if (strlen($filter_tree_slug))
        {
            $item = (new static)->setState('filter.slug', $filter_tree_slug)->getItem();
            if (empty($item->id)) 
            {
                // filter by a fake tree ID so that no results are returned for an invalid tree_slug
                $mongo_id = new \MongoId;
            }
            else 
            {
                $mongo_id = $item->id;
            }
            $this->setState('filter.tree', $mongo_id);
        }
        
        $filter_tree = $this->getState('filter.tree');
        if (!empty($filter_tree)) {
            $this->setCondition('tree', new \MongoId((string) $filter_tree ));
        }
        
        $filter_parent = $this->getState('filter.parent');
        if (!empty($filter_parent)) {
            $this->setCondition('parent', new \MongoId((string) $filter_parent ));
        }
    
        return $this;
    }
    
    protected function beforeValidate()
    {
        if (empty($this->slug) && !empty($this->title))
        {
            $this->slug = $this->generateSlug();
        }
        
        $this->path = $this->generatePath();
        
        return parent::beforeValidate();
    }
    
    /**
     * 
     */
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
            if ((empty($this->id) || $this->id != $existing->id) && $existing->type() == $this->type())
            {
                $this->setError( 'An item with this path already exists: ' . $this->path);
            }
        }
    
        return parent::validate();
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
        
        $this->slug = $this->generateSlug();
    }

    /**
     * beforeCreate is triggered before beforeSave,
     * and we ONLY want this to happen if all validations have passed
     */
    protected function beforeSave()
    {
        $this->tree = new \MongoId( (string) $this->tree );
        
        if (empty($this->parent)) {
            $this->parent = new \MongoId( (string) $this->tree );
        } elseif (!empty($this->parent)) {
            // is it a MongoId?
            $regex = '/^[0-9a-z]{24}$/';
            if (preg_match($regex, (string) $this->parent))
            {
                $this->parent = new \MongoId( (string) $this->parent );
            }
            else {
                $this->parent = null;
            }
        } else {
            $this->parent = null;
        }

        if (isset($this->published)) {
            $this->published = (bool) $this->published;
        }
        
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
        // get the old version so we can do some comparisons        
        $this->__oldNode = (new static)->load( array( '_id' => $this->id ) );
        
        // are we moving the node? or just updating its details?
        $this->__isMoving = false;        
        if (   $this->__oldNode->parent != $this->parent
            || $this->__oldNode->tree != $this->tree
        )
        {
            $this->__isMoving = true;
        }

        // do we need to update the children after save?
        $this->__update_children = isset($this->__update_children) ? $this->__update_children : false;
        if (   $this->__oldNode->tree != $this->tree 
            || $this->__oldNode->parent != $this->parent 
            || $this->__oldNode->title != $this->title 
            || $this->__oldNode->path != $this->path
        ) 
        {
            // update children after save
            $this->__update_children = true;
        }
        
        return parent::beforeUpdate();
    }
    
    protected function afterUpdate()
    {
        if ($this->__oldNode->tree != $this->tree)
        {
            // update the tree value for this node and all descendants
            $result = $this->collection()->update(
                array(
                    'lft' => array('$gte' => $this->__oldNode->lft, '$lte' => $this->__oldNode->rgt ),
                    'tree' => $this->__oldNode->tree
                ),
                array(
                    '$set' => array( 'tree' => $this->tree )
                ),
                array(
                    'multiple'=> true
                )
            );
        }
        
        if ($this->__update_children)
        {
            if ($children = (new static)->setState('filter.parent', $this->id)->getList())
            {
                foreach ($children as $child)
                {
                    $child->tree = $this->tree;
                    $child->path = null;
                    $child->__update_children = true;
                    $child->save();
                }
            }
        }        
        
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
    
    public function remove($options=array()) 
    {
        $this->__options = $options;
        
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
            $this->deleteDescendants();
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
     * Determines whether $this has descendants
     * 
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
    
    /**
     * Gets this model's parent, if possible
     * 
     * @return unknown|boolean
     */
    public function getParent()
    {
        $parent = (new static)->load( array('_id' => new \MongoId( (string) $this->parent ) ) );
        if (!empty($parent->id)) {
            return $parent;
        }
        
        return false;
    }
    
    /**
     * Get the immediate children of a model, $this one if no $parent is provided
     * 
     * @param unknown $parent
     * @return multitype:\Dsc\Mongo\Collections\Nested
     */
    public function getChildren( $parent=null )
    {
        if (empty($parent)) 
        {
            $parent_id = new \MongoId( (string) $this->id );
        }
        else 
        {
            $parent_id = new \MongoId( (string) $parent->id );
        }
        
        $this->cursor = $this->collection()->find( array(
        	'parent' => $parent_id,
            '_id' => array('$ne' => $parent_id)
        ) )->sort(array('lft' => 1));
        
        $result = array();
        foreach ($this->cursor as $doc) {
            $result[] = new static($doc);
        }

        return $result;
    }
    
    /**
     * Returns an array of descendants of this model, starting with $this.
     * The array will include $this;
     * 
     * @return multitype:\Dsc\Mongo\Collections\Nested
     */
    public function getDescendants()
    {
        $filter = array(
            'lft' => array('$gte' => $this->lft ),
            'rgt' => array('$lte' => $this->rgt ),
            'tree' => $this->tree
        );
    
        $this->cursor = $this->collection()->find( $filter )->sort(array('lft' => 1));
    
        $result = array();
        foreach ($this->cursor as $doc) {
            $result[] = new static($doc);
        }
    
        return $result;
    }
    
    /**
     * Delete this item's descendents
     * 
     * @param unknown $mapper
     * @return unknown
     */
    public function deleteDescendants()
    {
        $this->__last_operation = $this->collection()->remove(
        	array( 
                'lft' => array('$gt' => $this->lft ),
                'rgt' => array('$lt' => $this->rgt ),
                'tree' => $this->tree
            )
        );
            
        return $this;
    }
    
    /**
     * Gets the root of the specified tree, if possible
     * 
     * @param unknown $tree
     * @return \Dsc\Mongo\Collections\Nested|boolean
     */
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
    
    /**
     * Rebuilds the specified tree
     * starting with either the specified $node, or the root if none specified
     * 
     * @param unknown $tree
     * @param string $node
     * @param number $left
     * @return number
     */
    public function rebuildTree( $tree, $node=null, $left=1 ) 
    {
        if ($node === null)
        {
            $node = $this->getRoot( $tree );
        }

        if (empty($node)) 
        {
            $return = $left + 1;
            return $return;
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
        
        // not using $node->save() so we avoid recursion.  Just update the doc directly
        $cast = $node->cast();
        unset($cast['_id']);
                
        $this->collection()->update(
            array('_id'=> new \MongoId((string) $node->id ) ),
            array('$set' => $cast ),
            array('multiple'=>false)
        );
        
        // return the right value of this node + 1
        $return = $right + 1;
        
        return $return;
    }
    
    /**
     * Move this model one position to the left in the same level of the tree
     * 
     * @return \Dsc\Mongo\Collections\Nested
     */
    public function moveUp() 
    {
        $this->rebuildTree( $this->tree );
        
        // Get the sibling immediately to the left of this node
        $sibling = (new static)->load(array('tree' => $this->tree, 'rgt' => $this->lft - 1 ));

        // fail of no sibling found
        if (empty($sibling->id)) {
            return $this;
        }
        
        $ids = array();
        // Get the primary keys of descendant nodes, including this node's
        if ($descendants = $this->getDescendants()) {
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

        return $this;
    }
    
    /**
     * Move this model one position to the right in the same level of the tree
     * 
     * @return \Dsc\Mongo\Collections\Nested
     */
    public function moveDown()
    {
        $this->rebuildTree( $this->tree );
        
        // Get the sibling immediately to the left of this node
        $sibling = (new static)->load(array('tree' => $this->tree, 'lft' => $this->rgt + 1 ));
    
        // fail of no sibling found
        if (empty($sibling->id)) {
            return $this;
        }
    
        $ids = array();
        // Get the primary keys of descendant nodes, including this node's
        if ($descendants = $this->getDescendants()) {
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
    
        return $this;
    }
    
    /**
     * Determines the depth of this model in the tree
     * 
     * @return number
     */
    public function getDepth() 
    {
        if (!isset($this->depth)) 
        {
            $this->depth = substr_count( $this->path, "/" );
        }
        
        return (int) $this->depth;
    }
    
    /**
     * Rebuilds an array of trees
     * 
     * @param unknown $options
     * @return boolean
     */
    public static function rebuildTrees(array $trees=array())
    {
        foreach ($trees as $tree)
        {
            $model = new static;
            $model = $model->rebuildTree( $tree );
        }
        
        return true;
    }
    
    /**
     * Gets all the root level menu items for this type.
     *
     * @return array
     */
    public static function roots()
    {
        $model = new static;
        $return = $model->setState('filter.root', true)->setState('filter.type', $model->type() )->getItems();
    
        return $return;
    }
}
?>