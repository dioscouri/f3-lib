<?php 
namespace Dsc;

class System extends Singleton
{
    public $container = null;
    
    public function __construct($config=array())
    {
        parent::__construct( $config );

        $this->container = new \Dsc\Container;
        
        foreach ($config as $key=>$value) 
        {
        	$this->container->share( $key, $value );
        }
    }
    
    public function __get($key) 
    {
    	return $this->get($key);
    }
    
    public function get($key, $forceNew = false)
    {
        if (strtolower($key) === "container") {
        	return $this->container;
        }
        
        return $this->container->get($key, $forceNew);
    }
    
    public function set($key, $value, $shared = false, $protected = false)
    {
        if (strtolower($key) === "container") {
            $this->container = $value;
            return $this;
        }
    
        return $this->container->set($key, $value, $shared, $protected);
    }
        
    /**
     * 
     * @param unknown_type $message
     * @param unknown_type $type
     */
    public static function addMessage($message, $type='info') 
    {
        $messages = \Base::instance()->get('SESSION.messages') ? \Base::instance()->get('SESSION.messages') : array();
        
        switch (strtolower($type)) {
            case "good":
            case "success":
                $type = "success";
                break;
            case "high":
            case "bad":
            case "danger":
            case "error":
                $type = "danger";
                break;
            case "low":
            case "warn":
            case "warning":
            case "notice":
                $type = "warning";
                break;
            default:
                $type = "info";
                break;            
        }
        
        $messages = array_merge( $messages, array( array('message'=>$message, 'type'=>$type) ) );
        \Base::instance()->set('SESSION.messages', $messages);
    }
    
    public function getMessages($empty=true) 
    {
        $messages = \Base::instance()->get('SESSION.messages') ? \Base::instance()->get('SESSION.messages') : array();
        if ($empty) {
            \Base::instance()->set('SESSION.messages', array());
        }
        return $messages;
    }
    
    public function renderMessages() 
    {
        // Initialise variables.
        $buffer = null;
        $lists = array();
        
        // Get the message queue
        $messages = $this->getMessages();
        
        // Build the sorted message list
        if (is_array($messages) && !empty($messages))
        {
            foreach ($messages as $msg)
            {
                if (isset($msg['type']) && isset($msg['message']))
                {
                    $lists[$msg['type']][] = $msg['message'];
                }
            }
        }
        
        // If messages exist render them
        if (!empty($lists))
        {
            // Build the return string            
            $buffer .= "<div id='system-message-container'>";
            foreach ($lists as $type => $msgs)
            {
                $buffer .= "<div id='system-message-" . strtolower($type) . "' class='alert alert-" . strtolower($type) . "'>";
                if (!empty($msgs))
                {
                    $buffer .= "<ul class='list-unstyled'>";
                    foreach ($msgs as $msg)
                    {
                        $buffer .= "<li>" . $msg . "</li>";
                    }
                    $buffer .= "</ul>";
                }
                $buffer .= "</div>";
            }
            $buffer .= "</div>";
        }
        
        
        
        return $buffer;        
    }
    
    /**
     * 
     * @return \Joomla\Registry\Registry
     */
    public function getSessionRegistry()
    {
        $global_app_name = \Base::instance()->get('APP_NAME');
        $registry = \Base::instance()->get('SESSION.' . $global_app_name . '.system.registry');
        if (empty($registry) || !$registry instanceof \Joomla\Registry\Registry) {
            $registry = new \Joomla\Registry\Registry;
            \Base::instance()->set('SESSION.' . $global_app_name . '.system.registry', $registry);
        }
        
        return $registry;
    }
    
    /**
     * Gets a user state.
     *
     * @param   string  $key      The path of the state.
     * @param   mixed   $default  Optional default value, returned if the internal value is null.
     *
     * @return  mixed  The user state or null.
     */
    public function getUserState($key, $default = null)
    {
        $registry = $this->getSessionRegistry();
    
        if (!is_null($registry))
        {
            return $registry->get($key, $default);
        }
    
        return $default;
    }
    
    /**
     * Gets the value of a user state variable.
     *
     * @param   string  $key      The key of the user state variable.
     * @param   string  $request  The name of the variable passed in a request.
     * @param   string  $default  The default value for the variable if not found. Optional.
     * @param   string  $type     Filter for the variable, for valid values see {@link \Joomla\Filter\InputFilter::clean()}. Optional.
     *
     * @return  object  The request user state.
     */
    public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
    {
        $cur_state = $this->getUserState($key, $default);
        $new_state = $this->input->get($request, null, $type);
    
        // Save the new value only if it was set in this request.
        if ($new_state !== null)
        {
            $this->setUserState($key, $new_state);
        }
        else
        {
            $new_state = $cur_state;
        }
    
        return $new_state;
    }
    
    /**
     * Sets the value of a user state variable.
     *
     * @param   string  $key    The path of the state.
     * @param   string  $value  The value of the variable.
     *
     * @return  mixed  The previous state, if one existed.
     */
    public function setUserState($key, $value)
    {
        $registry = $this->getSessionRegistry();
        
        if (!empty($registry) && $registry instanceof \Joomla\Registry\Registry)
        {
            return $registry->set($key, $value);
        }
    
        return null;
    }
    
    /**
     * 
     */
    public function getDispatcher()
    {
        if (empty($this->dispatcher)) {
            $this->dispatcher = new \Joomla\Event\Dispatcher;
        }
        
        return $this->dispatcher;
    }
    
    /**
     * Trigger an event using the system dispatcher
     * 
     * @param unknown $eventName
     * @param unknown $arguments
     */
    public function trigger( $eventName, $arguments=array() )
    {
        $event = new \Joomla\Event\Event( $eventName );
        foreach ($arguments as $key => $value )
        {
            $event->addArgument( $key, $value );
        }
        
        return $this->getDispatcher()->triggerEvent($event);
    }
    
    /**
     * Trigger the preflight event for any listeners that need to run something before the app executes
     * 
     */
    public function preflight()
    {
        return $this->trigger('onPreflight');
    }
}
?>