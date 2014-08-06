<?php
namespace Dsc\Mongo\Collections;

class Sessions extends \Dsc\Mongo\Collection
{
    protected $__collection_name = 'sessions';
    
    public $session_id;
    
    public $data;
    
    public $ip;
    
    public $csrf;
    
    public $agent;
    
    public $timestamp;
    
    public $user_id;        // f3-users user_id
    
    public $identity;       // string to identify the user
    
    public $site_id;
    
    /**
     * Force save() to use store()
     * so there is no performance impact when tracking sessions
     * 
     * @param unknown $document
     * @param unknown $options
     */
    public function save($document=array(), $options=array())
    {
        $this->bind($document, $options);
        
        return $this->store( $options );
    }
    
    /**
     * Store the model document directly to the database
     * without firing plugin events
     *
     * @param unknown $document
     * @param unknown $options
     * @return \Dsc\Mongo\Collection
     */
    public function store( $options=array() )
    {
        if (empty($this->site_id))
        {
            $this->site_id = \Base::instance()->get('APP_NAME');
        }
        
        $this->session_id = session_id();
        $this->timestamp = time();
        
        if (empty($this->_id)) 
        {
            $sessionData = static::collection()->findOne(array(
                'session_id' => $this->session_id,
                'site_id' => $this->site_id
            ));
            
            if (isset($sessionData['_id']))
            {
                $this->_id = $sessionData['_id'];
            }
            else 
            {
                $this->_id = new \MongoId;
            }
        }
        
        $identity = \Dsc\System::instance()->auth->getIdentity();
        if (!empty($identity->id)) 
        {
            $this->user_id = $identity->id;
            $this->identity = $identity->email; 
        }
        
        $fw = \Base::instance();
        $headers = $fw->get('HEADERS');
                
        $this->ip = $fw->get('IP');
        $this->agent = isset($headers['User-Agent']) ? $headers['User-Agent'] : null;
        
        $this->__options = $options + array(
            'upsert'=>true,
            'multiple'=>false,
            'w'=>0
        );
        
        $this->__last_operation = $this->collection()->update(
            array(
                'session_id' => $this->session_id,
                'site_id' => $this->site_id
            ),
            $this->cast(),
            $this->__options
        );
        
        return $this;
    }
    
    public static function cleanup($max=null)
    {
        if (empty($max)) 
        {
            $max = 1800; // 30 minutes
        }
        
        static::collection()->remove(array(
            'timestamp' => array(
                '$lt' => time() - $max
            )
        ), array(
            'w' => 0
        ));

        return true;
    }
    
    public static function throttledCleanup()
    {
        $settings = \Dsc\Mongo\Collections\Settings::fetch();
        if (empty($settings->last_sessions_cleanup) || $settings->last_sessions_cleanup < (time() - 3600)) 
        {
            $settings->last_sessions_cleanup = time();
            
            if (empty($settings->id)) {
                $settings->save();
            } else {
                $settings->store();
            }
            
            return static::cleanup();            
        }
        
        return null;
    }
}