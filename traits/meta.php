<?php
namespace Dsc\Traits;

trait Meta
{
    protected $methods = array();
 
    public function addMethod($methodName, $methodCallable)
    {
        if (!is_callable($methodCallable)) {
            throw new \InvalidArgumentException('Second param must be callable');
        }
        $this->methods[$methodName] = \Closure::bind($methodCallable, $this, get_class());
    }
 
    public function __call($methodName, array $args)
    {
        if (isset($this->methods[$methodName])) {
            return call_user_func_array($this->methods[$methodName], $args);
        }
 
        throw new \RuntimeException('Call to undefined method ' . get_class() . '::' . $methodName);
    } 
}
?>