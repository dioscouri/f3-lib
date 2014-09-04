<?php
namespace Dsc\Traits;

trait Container
{
    /**
     * Gets a key from the DI
     *
     * @param unknown $key
     */
    public function __get($key)
    {
        return \Dsc\System::instance()->get($key);
    }
}