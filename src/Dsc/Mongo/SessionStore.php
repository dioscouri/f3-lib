<?php
namespace Dsc\Mongo;

class SessionStore extends \Dsc\Mongo\Collection
{
    protected $__collection_name = 'sessions';

    public $session_id;

    public $data;

    public $ip;

    public $csrf;

    public $agent;

    public $timestamp;

    protected $__session_id;

    /**
     * Instantiate class
     * 
     * @param $db object            
     * @param $table string            
     *
     */
    public function __construct($source = array(), $options = array())
    {
        session_set_save_handler(
            array($this, 'open'), 
            array($this, 'close'), 
            array($this, 'read'), 
            array($this, 'write'), 
            array($this, 'destroy'), 
            array($this, 'cleanup')
        );
        
        register_shutdown_function('session_commit');
        
        @session_start();
        
        $fw = \Base::instance();
        
        $headers = $fw->get('HEADERS');
        
        if (($ip = $this->ip()) && $ip != $fw->get('IP') || ($agent = $this->agent()) && (!isset($headers['User-Agent']) || $agent != $headers['User-Agent']))
        {
            session_destroy();
            $fw->error(403);
        }
        
        $csrf = $fw->hash($fw->get('ROOT') . $fw->get('BASE')) . '.' . $fw->hash(mt_rand());
        
        $sessionData = static::collection()->findOne(array(
            '_id' => $this->__session_id = session_id()
        ));
        
        if (isset($sessionData['_id']))
        {
            $this->bind($sessionData);
            $this->set('csrf', $csrf);
            $this->store();            
        }        
    }

    /**
     * Open session
     * 
     * @return TRUE
     * @param $path string            
     * @param $name string            
     *
     */
    function open($path, $name)
    {
        return true;
    }

    /**
     * Close session
     * 
     * @return TRUE
     *
     */
    function close()
    {
        return null;
    }

    /**
     * Return session data in serialized format
     * 
     * @return string|FALSE
     * @param $id string            
     *
     */
    function read($id)
    {
        if ($id != $this->__session_id)
        {
            $sessionData = static::collection()->findOne(array(
                '_id' => $this->__session_id = $id
            ));
            
            if (isset($sessionData['_id']))
            {
                $this->bind($sessionData);
            }
        }
        
        return (empty($this->_id)) ? false : $this->data;
    }

    /**
     * Write session data
     * 
     * @return TRUE
     * @param $id string            
     * @param $data string            
     *
     */
    function write($id, $data)
    {
        $fw = \Base::instance();
        $sent = headers_sent();
        $headers = $fw->get('HEADERS');
        
        if ($id != $this->__session_id) 
        {
            $sessionData = static::collection()->findOne(array(
                '_id' => $this->__session_id = $id
            ));
            
            if (isset($sessionData['_id']))
            {
                $this->bind($sessionData);
            }                        
        }
        
        $csrf = $fw->hash($fw->get('ROOT') . $fw->get('BASE')) . '.' . $fw->hash(mt_rand());
        
        $this->set('session_id', $id);
        $this->set('data', $data);
        $this->set('csrf', $sent ? $this->csrf() : $csrf);
        $this->set('ip', $fw->get('IP'));
        $this->set('agent', isset($headers['User-Agent']) ? $headers['User-Agent'] : '');
        $this->set('timestamp', time());
        
        $this->store();
        
        if (!$sent)
        {
            if (isset($_COOKIE['_'])) {
                setcookie('_', '', strtotime('-1 year'));
            }
            
            call_user_func_array('setcookie', array(
                '_',
                $csrf
            ) + $fw->get('JAR'));
        }
        
        return true;
    }

    /**
     * Destroy session
     * 
     * @return TRUE
     * @param $id string            
     *
     */
    function destroy($id)
    {
        $this->remove();
        
        setcookie(session_name(), '', strtotime('-1 year'));
        unset($_COOKIE[session_name()]);
        header_remove('Set-Cookie');
        
        return true;
    }

    /**
     * Garbage collector
     * 
     * @return TRUE
     * @param $max int            
     *
     */
    function cleanup($max)
    {
        $this->__last_operation = $this->collection()->remove(array(
            'timestamp' => array(
                '$lt' => time() - $max
            )
        ), array(
            'w' => 0
        ));
        
        return true;
    }

    /**
     * Return anti-CSRF token
     * 
     * @return string|FALSE
     *
     */
    function csrf()
    {
        return (empty($this->_id)) ? false : $this->csrf;
    }

    /**
     * Return IP address
     * 
     * @return string|FALSE
     *
     */
    function ip()
    {
        return (empty($this->_id)) ? false : $this->ip;
    }

    /**
     * Return Unix timestamp
     * 
     * @return string|FALSE
     *
     */
    function timestamp()
    {
        return (empty($this->_id)) ? false : $this->timestamp;
    }

    /**
     * Return HTTP user agent
     * 
     * @return string|FALSE
     *
     */
    function agent()
    {
        return (empty($this->_id)) ? false : $this->agent;
    }
}
