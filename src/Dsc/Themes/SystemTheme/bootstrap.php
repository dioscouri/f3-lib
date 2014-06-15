<?php
class SystemThemeBootstrap extends \Dsc\Bootstrap
{
    protected $dir = __DIR__;
    protected $base = __DIR__;
    protected $namespace = 'SystemTheme';
    
    /**
     * Register this app's view files for all global_apps
     * @param string $global_app
     */
    protected function registerViewFiles($global_app)
    {
        \Dsc\System::instance()->get('theme')->registerViewPath($this->dir . '/' . $global_app . '/Views/', $this->namespace . '/' . $global_app . '/Views');
    }
}
$app = new SystemThemeBootstrap();