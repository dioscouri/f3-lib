<?php
namespace Dsc;

class Container extends \Joomla\DI\Container
{
    public function __construct( Container $parent = null )
    {
        parent::__construct( $parent );
        
        $this->setDefaults();
    }

    public function setDefaults()
    {
        $this->share( 'input', function() {
            return new \Joomla\Input\Input;
        } );
        
        $this->share( 'inputfilter', function() {
            return new \Joomla\Filter\InputFilter;
        } );
        
        $db_name = \Base::instance()->get('db.mongo.name');
        $db_host = \Base::instance()->get('db.mongo.host');
        if ($db_name && $db_host) {
            $this->share( 'mongo', function() use ($db_host, $db_name) {
                return new \DB\Mongo('mongodb://'.$db_host, $db_name);
            } );
        }
        
        $this->share( 'theme', function() {
            return new \Dsc\Theme;
        } );                    
        
        $this->share( 'router', function() {
        	return new \Dsc\Routes\Router;
        });
    }
}
?>