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
    
    public $path;
    
    protected function fetchConditions()
    {
        parent::fetchConditions();
            
        $filter_user = $this->getState('filter.user');
        if (strlen($filter_user))
        {
            if (static::isValidId($filter_user))
            {
                $this->setCondition('user_id', new \MongoId( (string) $filter_user ));
            }            
        }
        
        $filter_site = $this->getState('filter.site');
        if (strlen($filter_site))
        {
            $this->setCondition('site_id', $filter_site);
        }        
        
        $filter_session = $this->getState('filter.session');
        if (strlen($filter_session))
        {
            $this->setCondition('session_id', $filter_session);
        }        
        
        $filter_active_after = $this->getState('filter.active_after');
        if (strlen($filter_active_after))
        {
            $this->setCondition( '$and', array( 'timestamp' => array ( '$gt' => $filter_active_after ) ), 'append' );
        }
        
        $filter_active_before = $this->getState('filter.active_before');
        if (strlen($filter_active_before))
        {
            $this->setCondition( '$and', array( 'timestamp' => array ( '$lt' => $filter_active_before ) ), 'append' );
        }
        
        $filter_is_user = $this->getState('filter.is_user');
        if (is_bool($filter_is_user) && !empty($filter_is_user))
        {
            $this->setCondition('user_id', array('$exists' => true, '$nin' => array( '', null ) ));
        }
        else if (is_bool($filter_is_user) && empty($filter_is_user))
        {
            $this->setCondition('user_id', array('$in' => array( '', null ) ));
        }
    
        return $this;
    }
    
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
                
        $this->path = $fw->hive()['PATH'];
        
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
    
    public static function ago($time, $now=null)
    {
        $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
        $lengths = array("60","60","24","7","4.35","12","10");
    
        if (empty($now)) {
            $now = time();
        }
    
        $difference     = $now - $time;
        $tense         = "ago";
    
        for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
            $difference /= $lengths[$j];
        }
    
        $difference = round($difference);
    
        if($difference != 1) {
            $periods[$j].= "s";
        }
    
        return "$difference $periods[$j] ago";
    }
    
    /**
     *
     * @param string $after
     * @param string $before
     * @return unknown
     */
    public static function fetchActiveVisitors( $after=null, $site_id='site' )
    {
        if (is_null($after))
        {
            $last_active = 5; // TODO fetch from a setting  // 5 minutes ago
            $after = time() - ($last_active * 60);
        }
    
        $items = (new static)->setState('filter.active_after', $after)->setState('filter.site', $site_id)->getitems();
    
        return $items;
    }
    
    /**
     *
     * @param string $after
     * @param string $before
     * @return unknown
     */
    public static function fetchActiveUsers( $after=null, $site_id='site' )
    {
        if (is_null($after))
        {
            $last_active = 5; // TODO fetch from a setting  // 5 minutes ago
            $after = time() - ($last_active * 60);
        }
    
        $items = (new static)->setState('filter.active_after', $after)->setState('filter.is_user', true)->setState('filter.site', $site_id)->getitems();
    
        return $items;
    }

    /**
     * Gets the associated user object
     *
     * @return unknown
     */
    public function user()
    {
        $user = (new \Users\Models\Users)->load(array('_id'=>$this->user_id));
    
        return $user;
    }
}