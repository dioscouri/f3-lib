<?php
namespace Dsc\Traits\Models;

trait Auditable
{
    public function auditableInit() 
    {
        $this->registerHook( 'beforeUpdate', 'auditableBeforeUpdate' );
        $this->registerHook( 'afterUpdate', 'auditableAfterUpdate' );
        $this->registerHook( 'afterCreate', 'auditableAfterCreate' );
    }
    
    public function auditableBeforeUpdate() 
    {
        if (!empty($this->id) && empty($this->__old)) 
        {
            $this->__old = (new static)->load( array('_id' => new \MongoId( (string) $this->id ) ));
        }
    }
    
    public function auditableAfterUpdate()
    {
        if (!empty($this->__old)) 
        {
            $diff = $this->auditableDiff();
            if (!empty($diff)) 
            {
                // TODO Save the diff in the audit collection
                // set type = $this class_nam
                // and set id = $this->id
            }
        }
    }
    
    public function auditableAfterCreate() 
    {
        // TODO add an entry for the creation of $this object
    }
    
    public function auditableDiff() 
    {
        $diff = array();
        
        if (!empty($this->__old))
        {
            $oldArray = $this->__old->cast();
            $thisArray = $this->cast();
            
            foreach ($thisArray as $key=>$value) 
            {
                $to = json_encode($value);
                
                if (!isset($oldArray[$key])) 
                {
                    $diff[$key] = array(
                        'from' => null,
                        'to' => $to
                    );
                }
                else 
                {
                    $from = json_encode($oldArray[$key]);
                    
                    if ($to != $from) 
                    {
                        $diff[$key] = array(
                            'from' => $from,
                            'to' => $to
                        );
                    }
                }
            }
        }

        return $diff;
    }
}