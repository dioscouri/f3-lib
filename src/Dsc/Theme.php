<?php
namespace Dsc;

class Theme extends \View
{
    protected $dsc_theme = array(
        'themes' => array( // themes are style sets for the entire application
            'current' => null,
            'paths' => array() 
        ),
        'variants' => array( // a different version of the same theme
            'current' => 'index.php' 
        ),
        'views' => array( // display related to a controller action, or just a block of html
            'current' => null,
            'paths' => array() 
        ),
        'buffers' => array() 
    );

    public function __construct( $config = array() )
    {
    }
    
    public function __get($key)
    {
        if (strtolower($key)=='app') {
            return \Base::instance();
        }
        
        return \Dsc\System::instance()->get($key);
    }

    /**
     * Register the path for a theme
     *
     * @param unknown $path            
     * @param string $name            
     */
    public function registerThemePath( $path, $name )
    {
        // TODO str_replace(\\ with /)
        // TODO ensure that the path has a trailing slash
        // TODO ensure that the path exists
        // TODO ensure that the path has an index.php in it
        \Dsc\ArrayHelper::set( $this->dsc_theme, 'themes.paths.' . $name, $path );
        
        return $this;
    }

    /**
     * Register a view path
     *
     * @param unknown $path            
     * @param string $key            
     */
    public function registerViewPath( $path, $key )
    {
        // str_replace(\\ with /)
        $path = str_replace( "\\", "/", $path );
        // TODO ensure that the path has a trailing slash
        // TODO ensure the path exists
        
        \Dsc\ArrayHelper::set( $this->dsc_theme, 'views.paths.' . $key, $path );
        
        return $this;
    }
    
    /**
     * Alias for render.  Only keeping it to ease transition from \Dsc\Template to \Dsc\Theme
     *
     * @param unknown $file
     * @param string $mime
     * @param array $hive
     * @param number $ttl
     */
    public function render( $file, $mime='text/html', array $hive=NULL, $ttl=0 )
    {
        return static::renderTheme( $file, array(
                        'mime' => $mime,
                        'hive' => $hive,
                        'ttl' => $ttl
        ) );
    }

    /**
     * Renders a theme, template, and view, defaulting to the currently set theme if none is specified
     */
    public function renderTheme( $view, array $params = array(), $theme_name = null )
    {
        $params = $params + array(
            'mime' => 'text/html', 
            'hive' => null, 
            'ttl' => 0
        ); 

        // TODO Before loading the variant file, ensure it exists. If not, load index.php or throw a 500 error
        // Render the theme
        $theme = $this->loadFile( $this->getThemePath( $this->getCurrentTheme() ) . $this->getCurrentVariant() );
              
        // Render the view
        $view_string = $this->renderView( $view, $params );

        // render the system messages
        $messages = \Dsc\System::instance()->renderMessages();
        $this->setBuffer( $messages, 'system.messages' );
        
        // get the view and the theme tags
        $view_tags = $this->getTags( $view_string );
        $theme_tags = $this->getTags( $theme );
        $all_tags = array_merge( $theme_tags, $view_tags );
        
        // Render any modules
        if (class_exists( '\\Modules\\Factory' ))
        {
            // Render the requested modules
            foreach ( $all_tags as $full_string => $args )
            {
                if (in_array( strtolower( $args['type'] ), array(
                                'modules'
                ) ) && ! empty( $args['name'] ))
                {
                    // get the requested module position content
                    $content = \Modules\Factory::render( $args['name'], \Base::instance()->get( 'PARAMS.0' ) );
                    $this->setBuffer( $content, $args['type'], $args['name'] );
                }
            }
        }        
        
        // and replace the tags in the view with their appropriate buffers
        $view_string = $this->replaceTagsWithBuffers( $view_string, $view_tags );
        $this->setBuffer( $view_string, 'view' );        
        
        // Finally replace any of the tags in the theme with their appropriate buffers
        $string = $this->replaceTagsWithBuffers( $theme, $theme_tags );

        return $string;
    }
    
    /**
     * Alias for renderView.  Only keeping it to ease transition from \Dsc\Template to \Dsc\Theme
     *  
     * @param unknown $file
     * @param string $mime
     * @param array $hive
     * @param number $ttl
     */
    public function renderLayout( $file, $mime='text/html', array $hive=NULL, $ttl=0 )
    {
        return static::renderView( $file, array(
            'mime' => $mime,
            'hive' => $hive,
            'ttl' => $ttl
        ) );
    }

    /**
     * Renders a view file, with support for overrides
     *
     * @param unknown $view
     * @param array $params
     * @return unknown|NULL
     */
    public function renderView( $view, array $params = array() )
    {
        $params = $params + array(
            'mime' => 'text/html',
            'hive' => null,
            'ttl' => 0
        );
        
        $string = null;
        
        if ($view_file = $this->findViewFile( $view )) 
        {
            $string = $this->loadFile($view_file);
        }
        
        return $string;
    }

    /**
     * Sets the theme to be used for the current rendering, but only if it has been registered.
     * if a path is provided, it will be registered.
     *
     * @param unknown $theme            
     */
    public function setTheme( $theme, $path = null )
    {
        if ($path)
        {
            $this->registerThemePath( $path, $theme );
        }
        
        if (\Dsc\ArrayHelper::exists( $this->dsc_theme, 'themes.paths.' . $theme ))
        {
            \Dsc\ArrayHelper::set( $this->dsc_theme, 'themes.current', $theme );
        }
        
        return $this;
    }

    public function setVariant( $name )
    {
        $filename = $name;
        $ext = substr( $filename, - 4 );
        if ($ext != '.php')
        {
            $filename .= '.php';
        }
        
        // TODO ensure that the variant filename exists in the theme folder?
        \Dsc\ArrayHelper::set( $this->dsc_theme, 'variants.current', $filename );
        
        return $this;
    }

    /**
     * Gets the current set theme
     */
    public function getCurrentTheme()
    {
        if ($theme = \Dsc\ArrayHelper::get( $this->dsc_theme, 'themes.current' )) {
        	return $theme;
        }
        
        throw new \Exception( 'You must set a theme.' );
    }

    /**
     * Gets the current set variant
     */
    public function getCurrentVariant()
    {
        return \Dsc\ArrayHelper::get( $this->dsc_theme, 'variants.current' );
    }

    /**
     * Gets the current set theme
     */
    public function getCurrentView()
    {
        return \Dsc\ArrayHelper::get( $this->dsc_theme, 'views.current' );
    }

    /**
     * Gets a theme's path by theme name
     */
    public function getThemePath( $name )
    {
        return \Dsc\ArrayHelper::get( $this->dsc_theme, 'themes.paths.' . $name );
    }

    /**
     * Gets a view's path by name
     */
    public function getViewPath( $name )
    {   
        return \Dsc\ArrayHelper::get( $this->dsc_theme, 'views.paths.' . $name );
    }

    /**
     * Gets all registered themes
     *
     * @return array
     */
    public function getThemes()
    {
        $return = (array) \Dsc\ArrayHelper::get( $this->dsc_theme, 'themes.paths' );
        
        return $return;
    }

    /**
     * Return any tmpl tags found in the string
     *
     * @return \Dsc\Theme
     */
    public function getTags( $file )
    {
        $matches = array();
        $tags = array();
        
        if (preg_match_all( '#<tmpl\ type="([^"]+)" (.*)\/>#iU', $file, $matches ))
        {
            $count = count( $matches[0] );
            for($i = 0; $i < $count; $i ++)
            {
                $type = $matches[1][$i];
                $attribs = empty( $matches[2][$i] ) ? array() : $this->parseAttributes( $matches[2][$i] );
                $name = isset( $attribs['name'] ) ? $attribs['name'] : null;
                
                $tags[$matches[0][$i]] = array(
                    'type' => $type,
                    'name' => $name,
                    'attribs' => $attribs 
                );
            }
        }
        
        return $tags;
    }

    /**
     * Method to extract key/value pairs out of a string with XML style attributes
     *
     * @param string $string
     *            String containing XML style attributes
     * @return array Key/Value pairs for the attributes
     */
    public function parseAttributes( $string )
    {
        $attr = array();
        $retarray = array();
        
        preg_match_all( '/([\w:-]+)[\s]?=[\s]?"([^"]*)"/i', $string, $attr );
        
        if (is_array( $attr ))
        {
            $numPairs = count( $attr[1] );
            for($i = 0; $i < $numPairs; $i ++)
            {
                $retarray[$attr[1][$i]] = $attr[2][$i];
            }
        }
        
        return $retarray;
    }

    public function replaceTagsWithBuffers( $file, array $tags )
    {
        $replace = array();
        $with = array();
        
        foreach ( $tags as $full_string => $args )
        {
            $replace[] = $full_string;
            $with[] = $this->getBuffer( $args['type'], $args['name'] );
        }
        
        return str_replace( $replace, $with, $file );
    }

    public function loadFile( $path )
    {
        $fw = \Base::instance();
        extract($fw->hive());
        
        ob_start();
        require $path;
        $file_contents = ob_get_contents();
        ob_end_clean();
        
        return $file_contents;
    }

    public function setBuffer( $contents, $type, $name = null )
    {
        if (empty( $name ))
        {
            $name = 0;
        }
        
        \Dsc\ArrayHelper::set( $this->dsc_theme, 'buffers.' . $type . "." . $name, $contents );
        
        return $this;
    }

    public function getBuffer( $type, $name = null )
    {
        if (empty( $name ))
        {
            $name = 0;
        }
        
        return \Dsc\ArrayHelper::get( $this->dsc_theme, 'buffers.' . $type . "." . $name );
    }

    /**
     * Shortcut for triggering an event within a view
     *
     * @param unknown $eventName
     * @param unknown $arguments
     */
    public function trigger( $eventName, $arguments = array() )
    {
        $event = new \Joomla\Event\Event( $eventName );
        foreach ( $arguments as $key => $value )
        {
            $event->addArgument( $key, $value );
        }
        
        return \Dsc\System::instance()->getDispatcher()->triggerEvent( $event );
    }
    
    /**
     * Finds the path to the requested view, accounting for overrides
     * 
     * @param unknown $view
     * @return Ambigous <boolean, string>
     */
    public function findViewFile( $view )
    {
        $string = false;
        
        $view = str_replace( "\\", "/", $view );
        $pieces = \Dsc\String::split( str_replace( array(
            "::",
            ":"
        ), "|", $view ) );
        
        // Overrides!
        // an overrides folder exists in this theme, let's check for the presence of an override for the requested view file
        $dir = \Dsc\Filesystem\Path::clean( $this->getThemePath( $this->getCurrentTheme() ) . "Overrides/" );
        if ($dir = \Dsc\Filesystem\Path::real( $dir ))
        {
            if (count( $pieces ) > 1)
            {
                // we're looking for a specific view (e.g. Blog/Site/View::posts/category.php)
                $view_string = $pieces[0];
                $requested_file = $pieces[1];
                $requested_folder = (dirname( $pieces[1] ) == ".") ? null : dirname( $pieces[1] );
                $requested_filename = basename( $pieces[1] );
            }
            else
            {
                // (e.g. posts/category.php) that has been requested, so look for it in the overrides dir
                $view_string = null;
                $requested_file = $pieces[0];
                $requested_folder = (dirname( $pieces[0] ) == ".") ? null : dirname( $pieces[0] );
                $requested_filename = basename( $pieces[0] );
            }
        
            $path = \Dsc\Filesystem\Path::clean( $dir . "/" . $view_string . "/" . $requested_folder . "/" );
        
            if ($path = \Dsc\Filesystem\Path::real( $path ))
            {
                $path_pattern = $path . $requested_filename;
                if (file_exists($path_pattern))
                {
                    $string = $path_pattern;
                    return $string;
                }
            }
        }
        
        if (count( $pieces ) > 1)
        {
            // we're looking for a specific view (e.g. Blog/Site/View::posts/category.php)
            // $view is a specific app's view/template.php, so try to find it
            $view_string = $pieces[0];
            $requested_file = $pieces[1];
        
            $view_dir = $this->getViewPath( $view_string );
        
            $path_pattern = $view_dir . $requested_file;
        
            if (file_exists($path_pattern)) 
            {
                $string = $path_pattern;
            }
        }
        else
        {
            $requested_file = $pieces[0];
            // it's a view in the format 'common/pagination.php'
            // try to find it in the registered paths
            foreach (\Dsc\ArrayHelper::get( $this->dsc_theme, 'views.paths' ) as $view_path)
            {
                $path_pattern = $view_path . $requested_file;
                if (file_exists($path_pattern)) 
                {
                    $string = $path_pattern;
                    break;
                }
            }
        }

        return $string;
    }
}
?>