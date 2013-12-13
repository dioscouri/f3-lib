<?php 
namespace Dsc\Models\Db;

class Jig extends \Dsc\Models\Db\Base 
{
    protected $filename = "jig"; // the jig filename for the model
    
    protected function createDb()
    {
        $this->db = new \DB\Jig( \Base::instance()->get('db.jig.dir'), \DB\Jig::FORMAT_JSON );
        
        return $this;
    }
    
    public function getMapper()
    {
        $mapper = new \DB\Jig\Mapper( $this->getDb(), $this->filename );
        return $mapper;
    }
    
    public function drop()
    {
        $this->getDb()->write( $this->filename, null );
        
        return $this;
    }
    
    public function setFilename($filename)
    {
        $this->filename = $filename;
        
        return $this;
    }
}
?>