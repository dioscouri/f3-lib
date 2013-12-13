<?php 
namespace Dsc\Models\Db;

abstract class Base extends \Dsc\Model 
{
    protected $db = null; // the db connection object
    
    public function getDb()
    {
        if (empty($this->db))
        {
            $this->createDb();
        }
    
        return $this->db;
    }
    
    abstract protected function createDb();
}
?>