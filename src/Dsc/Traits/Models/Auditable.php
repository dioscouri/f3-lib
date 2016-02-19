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
    
    /**
     * Store the previous copy of $this in __old
     */
    public function auditableBeforeUpdate() 
    { 
        if (!empty($this->id) && empty($this->__old)) 
        {
            $this->__old = (new static)->load( array('_id' => new \MongoId( (string) $this->id ) ));
        }
    }
    
    /**
     * add an entry for the update of $this object
     */
    public function auditableAfterUpdate()
    {
        if (!empty($this->__old)) 
        {
            $diff = $this->auditableDiff();
            if (!empty($diff)) 
            {
                $action = 'UPDATED';
                $message = $this->auditableTitle() . ' was updated'; 
                $actor = $this->auditableGetActor();
                
                \Dsc\Mongo\Collections\AuditLogs::add( array(
                    'type' => get_class($this),
                    'id' => $this->id
                ), $action, $diff, $actor, $message );
            }
        }
    }
    
    /**
     * add an entry for the creation of $this object
     * 
     */
    public function auditableAfterCreate() 
    {
        $diff = null;
        
        $action = 'CREATED';
        $message = $this->auditableTitle() . ' was created';
        $actor = $this->auditableGetActor();
    
        \Dsc\Mongo\Collections\AuditLogs::add( array(
            'type' => get_class($this),
            'id' => $this->id
        ), $action, $diff, $actor, $message );        
    }
    
    /**
     * Get the diff
     * 
     * @return multitype:multitype:NULL unknown  multitype:unknown
     */
    public function auditableDiff() 
    {
        $diff = array();
       
        if (!empty($this->__old))
        {
            $oldArray = $this->__old->cast();
            $thisArray = $this->cast();
            
            foreach ($thisArray as $key=>$value) 
            {
                if (in_array($key, $this->auditableGetIgnoredKeys())) {
                    continue;
                }
                
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
    
    /**
     * Returns the name of a class using get_class with the namespaces stripped.
     *
     * @param  object|string  $object  Object or Class Name to retrieve name
    
     * @return  string  Name of class with namespaces stripped
     */
    public function auditableGetClassName($object = null)
    {
        if (!is_object($object) && !is_string($object)) {
            return null;
        }
    
        $class = explode('\\', (is_string($object) ? $object : get_class($object)));
        return $class[count($class) - 1];
    }
    
    /**
     * Returns the title of $this,
     * for storing in the display message for the audit log.
     * Expected to be overridden
     * 
     * @return string
     */
    public function auditableTitle() 
    {
        $title = null;
        
        if (!empty($this->__auditableTitle)) {
            $title = $this->__auditableTitle;
        } elseif ($this->title) {
            $title = $this->title;
        } elseif ($this->name) {
            $title = $this->name;
        } else {
            $title = $this->auditableGetClassName( $this );
        }
        
        return $title;
    }
    
    /**
     * Get the actor.  Can be overridden
     * 
     * @return multitype:string NULL
     */
    public function auditableGetActor()
    {
        $actor = array(
            'type' => 'system',
            'id' => null,
            'name' => 'System Process'
        );
        
        if (!empty($this->__auditableActor)) 
        {
            $actor = $this->__auditableActor;
        } 
        else 
        {
            $identity = \Dsc\System::instance()->get('auth')->getIdentity();
            if (!empty($identity->id))
            {
                $actor = array(
                    'type' => 'user',
                    'id' => $identity->id,
                    'name' => $identity->fullName(),
                );
            }
        }
        
        return $actor;
    }
    
    public function auditableGetIgnoredKeys() 
    {
        $keys = $this->__auditableIgnoredKeys ? $this->__auditableIgnoredKeys : array(
            'metadata'
        );
        
        if (!is_array($keys)) {
            $keys = (array) $keys;
        }
        
        return $keys;
    }
    /**
     * Returns Paginated List of logs for object
     */
    public function auditableGetLogs() {
        
        return (new \Dsc\Mongo\Collections\AuditLogs)->setState('filter.resource_id', $this->id)->paginate();
    }
}