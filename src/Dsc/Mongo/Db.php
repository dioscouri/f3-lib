<?php
namespace Dsc\Mongo;

class Db extends \DB\Mongo 
{
    public function db() {
        return $this->db;
    }
}