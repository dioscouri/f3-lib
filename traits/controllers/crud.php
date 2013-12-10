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
        
        return $this->doCreate($data);
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
        
        return $this->doRead($data, $this->getItemKey());    
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
        
        return $this->doUpdate($data, $this->getItemKey());    
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
        
        return $this->doDelete($data, $this->getItemKey());    
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
    
        return $this->doAdd($data);
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
    
        return $this->doEdit($data, $this->getItemKey());
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