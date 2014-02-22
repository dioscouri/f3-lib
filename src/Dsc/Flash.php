<?php 
namespace Dsc;

class Flash extends Singleton
{
    protected $context = '\dsc\flash';

    /**
     * Store an input array to the session.
     * Useful when displaying a prepopulated form,
     * such as after validation fails.
     *  
     * @param array $values
     * @return \Dsc\Input
     */
    public function store( array $values ) 
    {
        $this->set('old', $values);
        
        return $this;
    }

    /**
     * Store only the selected keys from the provided input array
     *  
     * @param array $keys
     * @param array $values
     * @return \Dsc\Flash
     */
    public function only( array $keys, array $values )
    {
        $values = \Dsc\ArrayHelper::only($values, $keys) + array_fill_keys($keys, null);
        $this->store($values);
        
        return $this;
    }
    

    /**
     * Store everything except the selected keys from the provided input array
     * 
     * @param array $keys
     * @param array $values
     * @return \Dsc\Flash
     */
    public function except( array $keys, array $values )
    {
        $result = $values;
        
        foreach ($keys as $key) 
        {
            \Dsc\ArrayHelper::forget($result, $key);
        }
        $this->store($result);
        
        return $this;
    }
    
    /**
     * Gets a value from the input array stored in the session using $this->store();
     * $key can be in dot notation to get values from deep within arrays
     * 
     * @param string $key
     * @param unknown_type $default
     */
    public function old( $key, $default=null )
    {
        $array = $this->get('old');
        $value = \Dsc\ArrayHelper::get( $array, $key, $default );
        
        return $value;        
    }
    
    /**
     * 
     * @param unknown_type $key
     * @param unknown_type $value
     * @return \Dsc\Input
     */
    public function set( $key, $value ) 
    {
        $system = \Dsc\System::instance();
        $key = $this->context . "." . $key;
        $system->setUserState($key, $value);
        
        return $this;
    }
    
    /**
     * 
     * @param unknown_type $key
     * @param unknown_type $default
     * @return unknown
     */
    public function get( $key, $default=null )
    {
        $system = \Dsc\System::instance();
        $key = $this->context . "." . $key;
        $value = $system->getUserState($key, $default);        
        
        return $value;
    }
}