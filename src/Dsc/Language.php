<?php 
namespace Dsc;

class Language extends \Dsc\Singleton
{
    public function __construct($config=array())
    {
        $this->model = new \Dsc\Mongo\Collections\Translations\Strings;
        $this->model = $this->model->load(array('language_code' => $this->app->get('lang')));
    }
    
    public function get($key, $default=null)
    {
        $slug = \Web::instance()->slug( $key );

        if ($this->model->exists('strings.' . $slug)) 
        {
            return $this->model->{'strings.' . $slug};
        }
        
        if (!(new \Dsc\Mongo\Collections\Translations\Keys)->slugExists($slug)) 
        {
            (new \Dsc\Mongo\Collections\Translations\Keys)->set('title', $key)->set('slug', $slug)->save();
        }
        
        return !empty($default) ? $default : $key;
    }
}