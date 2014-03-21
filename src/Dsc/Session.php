<?php 
namespace Dsc;

class Session
{
    public function __construct($store)
    {
        $this->store = $store;
    }
    
    public function id()
    {
        return session_id();
    }
    
    public function get( $key )
    {
        return \Base::instance()->get('SESSION.' . $key );
    }
    
    public function set( $key, $value )
    {
        \Base::instance()->set('SESSION.' . $key , $value );
    }
    
    public function remove( $key )
    {
        $this->set( $key, null );
    }
}
?>