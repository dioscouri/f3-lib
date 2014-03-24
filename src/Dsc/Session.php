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
    
    public function destroy()
    {
        \Base::instance()->clear('SESSION');
        session_unset();
        setcookie(session_name(),'',strtotime('-1 year'));
        unset($_COOKIE[session_name()]);
        header_remove('Set-Cookie');
        session_regenerate_id(true);        
        
        session_start();
        return session_destroy();
    }
}
?>