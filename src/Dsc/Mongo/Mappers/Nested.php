<?php
namespace Dsc\Mongo\Mappers;

class Nested extends \Dsc\Mongo\Mapper
{
    public function insert()
    {
        unset( $parent );
        $parent = clone $this;
        $parent->reset();
        if (empty( $this->parent ) && empty( $this->is_root ))
        {
			$root = $this->getRoot( $this->tree );
			$this->parent = $root->id;
			
			$parent->load( array('_id'=> new \MongoId( (string) $this->parent ) ) );
        }
        
        if ($parent->hasDescendants())
        {
            $rgt = $parent->rgt;
        
            // UPDATE nested_category SET rgt = rgt + 2 WHERE rgt >= @myRight;
            $result = $this->collection->update(
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
            $result = $this->collection->update(
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
            $result = $this->collection->update(
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
            $result = $this->collection->update(
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
        
        return parent::insert();
    }

    public function update()
    {
        // are we moving the node? or just updating its details?
        $moving = false;
        $node = clone $this;
        $node->load( array( '_id' => $this->id ) );
        if ($node->parent != $this->parent)
        {
            $moving = true;
        }
        
        $return = parent::update();
        
        if ($moving)
        {
            $this->rebuildTree( $this->tree );
            
            // if we just removed a leaf/branch to a new tree, rebuild the old tree too 
            if ($this->tree != $node->tree) 
            {
                $this->rebuildTree( $node->tree );
            }
        }
        
        return $return;
    }
    
    public function erase($filter=NULL) 
    {
        // DELETE FROM nested_category WHERE lft BETWEEN @myLeft AND @myRight; 
        $result = $this->collection->remove(
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
        
        $result = $this->collection->update(
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
        $result = $this->collection->update(
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
        
        return true;
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
    
    public function getChildren( $mapper )
    {
        $filter = array(
        	'parent' => $mapper->id
        );
        
        $this->cursor = $this->collection->find( $filter, array() );
        $this->cursor = $this->cursor->sort(array('ordering' => 1));
        
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
    
        $this->cursor = $this->collection->find( $filter, array() );
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
        $result = $this->collection->remove(
        	array( 
                'lft' => array('$gt' => $mapper->lft ),
                'rgt' => array('$lt' => $mapper->rgt ),
                'tree' => $mapper->tree
            )
        );
            
        return $result;
    }
    
    public function getRoot( $tree ) 
    {
        $root = clone $this;
        $root->reset();
        $root->load(array(
        	'tree' => new \MongoId((string) $tree),
            'is_root' => true
        ));
        
        return $root;
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
        $result = $this->collection->update(
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
        $result = $this->collection->update(
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
        $result = $this->collection->update(
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
        $result = $this->collection->update(
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
}
?>