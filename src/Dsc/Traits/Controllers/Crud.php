<?php
namespace Dsc\Traits\Controllers;
 
trait Crud 
{
    /**
     * Displays form for creating new record
     */
    public function create() 
    {
        $inputfilter = new \Joomla\Filter\InputFilter;
        $data = \Base::instance()->get('REQUEST');
        
        if (!$this->canCreate($data)) {
            throw new \Exception('Not allowed to create record');
        }
        
        $this->doCreate($data);
        
        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    /**
     * Displays record
     */
    public function read()
    {
        $inputfilter = new \Joomla\Filter\InputFilter;
        $data = \Base::instance()->get('REQUEST');
        
        if (!$this->canRead($data, $this->getItemKey())) {
            throw new \Exception('Not allowed to read record');
        }
        
        $this->doRead($data, $this->getItemKey());

        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    /**
     * Update existing record
     */
    public function update()
    {
        $inputfilter = new \Joomla\Filter\InputFilter;
        $data = \Base::instance()->get('REQUEST');
        
        if (!$this->canUpdate($data, $this->getItemKey())) {
            throw new \Exception('Not allowed to update record');
        }
        
        $this->doUpdate($data, $this->getItemKey());

        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    /**
     * Delete record
     */
    public function delete()
    {
        $inputfilter = new \Joomla\Filter\InputFilter;
        $data = \Base::instance()->get('REQUEST');
        
        if (!$this->canDelete($data, $this->getItemKey())) {
            throw new \Exception('Not allowed to delete record');
        }
        
        $this->doDelete($data, $this->getItemKey());

        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    /**
     * Target for POST to create new record
     */
    public function add()
    {
        $inputfilter = new \Joomla\Filter\InputFilter;
        $data = \Base::instance()->get('REQUEST');
    
        if (!$this->canCreate($data)) {
            throw new \Exception('Not allowed to add record');
        }
    
        $this->doAdd($data);
        
        if ($route = $this->getRedirect()) {
            \Base::instance()->reroute( $route );
        }
        
        return;
    }
    
    /**
     * Displays record for editing
     */
    public function edit()
    {
        $inputfilter = new \Joomla\Filter\InputFilter;
        $data = \Base::instance()->get('REQUEST');
    
        if (!$this->canUpdate($data, $this->getItemKey())) {
            throw new \Exception('Not allowed to edit record');
        }
    
        $this->doEdit($data, $this->getItemKey());
        
        if ($route = $this->getRedirect()) {
            $f3->reroute( $route );
        }
        
        return;
    }
    
    protected function canCreate(array $data)
    {
        return true;
    }
    
    protected function canRead(array $data, $key=null)
    {
        return true;
    }
    
    protected function canUpdate(array $data, $key=null)
    {
        return true;
    }
    
    protected function canDelete(array $data, $key=null)
    {
        return true;
    }
    
    protected function canSave(array $data, $key=null)
    {
        $key = (empty($key)) ? $this->getItemKey() : $key;
        $identifier = isset($data[$key]) ? $data[$key] : null;
    
        if ($identifier)
        {
            return $this->canUpdate($data, $key);
        }
        else
        {
            return $this->canCreate($data);
        }
    }
    
    abstract protected function getItemKey();
    
    abstract protected function doCreate(array $data);
    
    abstract protected function doAdd(array $data);
    
    abstract protected function doRead(array $data, $key=null);
    
    abstract protected function doEdit(array $data, $key=null);
    
    abstract protected function doUpdate(array $data, $key=null);
    
    abstract protected function doDelete(array $data, $key=null);
}
?>