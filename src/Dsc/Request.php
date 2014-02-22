<?php 
namespace Dsc;

class Request extends Singleton
{
    /**
     * Attempts to load the specified controller and execute the requested method with the provided arguments
     * 
     * @param string $controller_method
     * @param array $arguments
     * @param array $options
     * 
     * @return string 
     */
    public static function internal( $controller_method, $arguments=array(), $options=array() ) 
    {
        $f3 = \Base::instance();
        $f3->set('HMVC', true);
        
        $hooks = !empty($options['hooks']) ? $options['hooks'] : null;
        
        try {
            $return = $f3->call( $controller_method, $arguments, $hooks );
        } catch (\Exception $e) {
            $return = $e->getMessage();
        }
        
        $f3->set('HMVC', false);
        
        return (string) $return;
    }
}