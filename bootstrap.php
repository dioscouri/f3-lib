<?php
class DscBootstrap extends \Dsc\Bootstrap
{
    protected $dir = __DIR__;

    protected $namespace = 'Dsc';

    protected function preAdmin()
    {
        parent::preAdmin();
        
        $this->setLang();
        
        $this->app->route('POST /admin/log [ajax]', '\DscBootstrap->log');
    }
    
    protected function preSite()
    {
        parent::preSite();
    
        $this->setLang();
        
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
    
    protected function setLang()
    {
        //$this->app->set('FALLBACK','en'); // this would set english as the fallback language
        $this->app->set('lang', 'en'); // set from config
        $this->app->set('PREFIX', '_');
    
        $domain = strtolower($this->app->get('HOST'));
        $pieces = explode(".", $domain);
        // does the first piece match any of the installed/enabled languages?
        $langs = array('es'); // TODO get from installed langs
        if (in_array($pieces[0], $langs))
        {
            $this->app->set('LANGUAGE', $pieces[0]);
            $this->app->set('lang', $pieces[0]);
            // TODO Load the fallback's translation key/value pairs
            // TODO Load this language's translation key/value pairs
            /*
             $model = new \Translations\Models\Strings;
            $model = $model->load(array('language_code' => $pieces[0]));
            $strings = array();
            if (!empty($model->strings)) {
            $strings = $model->strings;
            }
            $this->app->mset($strings, '_');
            */
        }
    }
}
$app = new DscBootstrap();