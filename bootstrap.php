<?php
class DscBootstrap extends \Dsc\Bootstrap
{
    protected $dir = __DIR__;

    protected $namespace = 'Dsc';

    protected function preAdmin()
    {
        parent::preAdmin();
        
        $this->app->route('POST /admin/log [ajax]', '\DscBootstrap->log');
    }
    
    protected function preSite()
    {
        parent::preSite();
    
        $this->app->route('POST /log [ajax]', '\DscBootstrap->log');
    }
    
    public function log()
    {
        $message = $this->input->get('message', null, 'string');
        $priority = $this->input->get('priority', 'INFO', 'string');
        $category = $this->input->get('category', 'General', 'string');
        
        if (!empty($message)) {
            \Dsc\Models::log( $message, $priority, $category );
        }        
    }
    
    protected function runAdmin()
    {
        $this->checkSymlink();
    }
    
    protected function runSite()
    {
        $this->checkSymlink();
    }
    
    protected function checkSymlink()
    {
        if (!is_dir($this->app->get('PATH_ROOT') . 'public/DscAssets'))
        {
            $target = $this->app->get('PATH_ROOT') . 'public/DscAssets';
            $source = realpath( $this->app->get('PATH_ROOT') . 'vendor/dioscouri/f3-lib/src/Dsc/Assets' );
            $res = symlink($source, $target);
        }
    }
}
$app = new DscBootstrap();