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
                
                \KM::record($action, $properties);                                
            }            
        }
    
        return null;
    }
}