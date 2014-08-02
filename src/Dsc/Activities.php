<?php
namespace Dsc;

class Activities extends Singleton
{
    public static function track($action, $properties=array())
    {
        if (class_exists('\Activity\Models\Actions'))
        {
            \Activity\Models\Actions::track($action, $properties);
        }
        
        if (class_exists('\Admin\Models\Settings') && class_exists('\KM')) 
        {
            $settings = \Admin\Models\Settings::fetch();
            if ($settings->enabledIntegration('kissmetrics') && $settings->{'integration.kissmetrics.key'}) 
            {
                \KM::init( $settings->{'integration.kissmetrics.key'} );
                
                $identity = \Dsc\System::instance()->get('auth')->getIdentity();
                if ($identity->email) 
                {
                    \KM::identify( $identity->email );
                } 
                elseif (isset($_COOKIE['km_ni'])) 
                {
                    \KM::identify( $_COOKIE['km_ni'] );
                }
                elseif (isset($_COOKIE['km_ai'])) 
                {
                    \KM::identify( $_COOKIE['km_ai'] );                
                }
                else 
                {
                    $mongo_id = new \MongoId;
                    \KM::identify( (string) $mongo_id );
                }
                
                \KM::record($action, $properties);
            }            
        }
    
        return null;
    }
    
    public static function trackActor($email, $action, $properties=array())
    {
        if (class_exists('\Activity\Models\Actions'))
        {
            \Activity\Models\Actions::trackActor($email, $action, $properties);
        }
        
        if (class_exists('\Admin\Models\Settings') && class_exists('\KM'))
        {
            $settings = \Admin\Models\Settings::fetch();
            if ($settings->enabledIntegration('kissmetrics') && $settings->{'integration.kissmetrics.key'})
            {
                \KM::init( $settings->{'integration.kissmetrics.key'} );
        
                \KM::identify($email);
        
                \KM::record($action, $properties);
            }
        }        
        
        return null;
    }    
}