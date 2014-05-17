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
        $this->share( 'app', function() {
            return \Base::instance();
        } );
                
        $this->share( 'input', function() {
            return new \Joomla\Input\Input;
        } );
        
        $this->share( 'inputfilter', function() {
            return new \Joomla\Filter\InputFilter;
        } );
        
        $db_name = \Base::instance()->get('db.mongo.database');
        $db_server = \Base::instance()->get('db.mongo.server');
        if ($db_name && $db_server) {
            $this->share( 'mongo', function() use ($db_server, $db_name) {
                return new \MongoDB( new \MongoClient($db_server), $db_name);
                // see this bug: https://jira.mongodb.org/browse/PHP-928
                // when it's resolved and part of the current PECL Mongo package, revert this.
                //return new \DB\Mongo($db_server, $db_name);
            } );
        }
        
        $this->share( 'theme', function() {
            return new \Dsc\Theme;
        } );                    
        
        $this->share( 'router', function() {
        	return new \Dsc\Routes\Router;
        });
        
        $this->share( 'acl', function() {
            return new \Users\Lib\Acl;
        });
        
        $this->share( 'auth', function() {
            return new \Users\Lib\Auth;
        });
        
        //$store = new \DB\Mongo\Session($this->get('mongo'));
        $store = null;
        $this->share( 'session', function() use($store) {
            return new \Dsc\Session($store);
        });
        
        $this->share( 'mailer', function() {
            return new \Mailer\Factory;
        });
    }
}
?>