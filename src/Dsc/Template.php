<?php 
namespace Dsc;

class Template extends \View
{
    protected $template_name = "default";
    protected $layout_name = "index.php";
    protected $template_file = null;
    protected $template_contents = array();
    protected $template_tags = array();
    protected $view_name = null;
    
    public function __construct($config=array())
    {
        $this->app = \Base::instance();
    }
    
    public function setTemplate($name) 
    {
        $fw = $this->app;
        foreach ($fw->split($fw->get('TEMPLATES')) as $dir)
        {
            if (is_dir($this->template_file_path=$fw->fixslashes($dir.$this->template_name."/")))
            {
                $this->template_name = $name;
                break;
            }
        }
        
        return $this;
    }
    
    public function setLayout($file)
    {
        $fw = $this->app;
        foreach ($fw->split($fw->get('TEMPLATES')) as $dir)
        {
            $this->template_file_path = $fw->fixslashes($dir.$this->template_name."/".$file);
            if (is_file($this->template_file_path))
            {
                $this->layout_name = $file;
                break;
            }
        }

        return $this;
    }
    
    public function renderSpecificLayout($dir, $file, $mime='text/html', array $hive=NULL)
    {
        $fw = $this->app;
        
        if (is_file($this->view=$fw->fixslashes($dir.$file))) {
        	if (isset($_COOKIE[session_name()]))
        		@session_start();
        	$fw->sync('SESSION');
        	if (!$hive)
        		$hive=$fw->hive();
        	if ($fw->get('ESCAPE'))
        		$hive=$fw->esc($hive);
        	if (PHP_SAPI!='cli')
        		header('Content-Type: '.$mime.'; '.
        				'charset='.$fw->get('ENCODING'));
        	return $this->sandbox($hive);
        }
    
        return $this;
    }
    
    public function renderLayout( $file, $mime='text/html', array $hive=NULL, $ttl=0 ) 
    {
        $fw = $this->app;
        
        $pieces = $fw->split(str_replace(array("::", ":"), "|", $file));
        if (count($pieces) > 1) {
            $view = str_replace("\\", "/", $pieces[0]);
            $file = $pieces[1];
            // if the requested specific file exists, use it,
            // otherwise let render search for the first $file match
            foreach ($fw->split($fw->get('UI')) as $dir) {
            	if (strpos($dir, $view) !== false) {
            		return $this->renderSpecificLayout($dir, $file, $mime, $hive);
            	}            	
            }
        } 
        
        return parent::render( $file, $mime, $hive, $ttl );        
    }
    
    public function render( $file,$mime='text/html',array $hive=NULL, $ttl=0 ) 
    {
        $this->setContents( $this->renderLayout( $file, $mime, $hive ), 'view' );
        $this->setContents( $this->renderLayout( "system-messages.php", $mime, $hive ), 'system.messages' );
        $this->loadTemplate()->parseTemplate();
        $string = $this->renderTemplate();
        
        return $string;
    }
    
    public function setContents( $contents, $type, $name=null ) 
    {
        if (empty($this->template_contents[$type])) {
            $this->template_contents[$type] = array();
        }
        
        $this->template_contents[$type][$name] = $contents;
        
        return $this;
    }
    
    public function getContents( $type, $name=null )
    {
        $return = null;
        
        if (!empty($this->template_contents[$type][$name])) {
            $return = $this->template_contents[$type][$name];
        }

        return $return;
    }
    
    /**
     *	Loads template
     *	@return object $this
     **/
    protected function loadTemplate() 
    {
        if (!empty($this->template_file)) {
            return $this;
        }
        
        $fw = $this->app;
        foreach ($fw->split($fw->get('TEMPLATES')) as $dir) 
        {
            if (is_file($this->template_file_path=$fw->fixslashes($dir.$this->template_name."/".$this->layout_name))) 
            {
                extract($this->app->hive());
                
                ob_start();
                require $this->template_file_path;
                $this->template_file = ob_get_contents();
                ob_end_clean();
                
                return $this;
            }
        }
        user_error(sprintf(\Base::E_Open,$file));
    }
    
    /**
     * Renders template
     * @return string 
     */
    protected function renderTemplate() 
    {
        $replace = array();
        $with = array();
        
        foreach ($this->template_tags as $full_string => $args)
        {
            $replace[] = $full_string;
            $with[] = $this->getContents($args['type'], $args['name']);
        }
        
        return str_replace($replace, $with, $this->template_file);
    }

    /**
     * 
     */
    protected function parseTemplate()
    {
        if (!empty($this->template_tags)) {
            return $this;
        }
        
        $matches = array();
        
        if (preg_match_all('#<tmpl\ type="([^"]+)" (.*)\/>#iU', $this->template_file, $matches)) 
        {
            $count = count($matches[0]); 
            for ($i=0; $i<$count; $i++)
            {
                $type = $matches[1][$i];
                $attribs = empty($matches[2][$i]) ? array() : $this->parseAttributes($matches[2][$i]);
                $name = isset($attribs['name']) ? $attribs['name'] : null;

                $this->template_tags[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
            }
        }
        
        return $this;
    }
    
    /**
     * Method to extract key/value pairs out of a string with XML style attributes
     *
     * @param   string  $string  String containing XML style attributes
     * @return  array  Key/Value pairs for the attributes
     */
    public static function parseAttributes($string)
    {
        $attr = array();
        $retarray = array();

        preg_match_all('/([\w:-]+)[\s]?=[\s]?"([^"]*)"/i', $string, $attr);

        if (is_array($attr))
        {
            $numPairs = count($attr[1]);
            for ($i = 0; $i < $numPairs; $i++)
            {
                $retarray[$attr[1][$i]] = $attr[2][$i];
            }
        }

        return $retarray;
    }
}
?>