<?php
namespace Dsc\Traits\Models;

trait Seo
{

    public $seo = array(
        'page_title' => null,
        'meta_description' => null
    );

    public function seoTitle()
    {
        $return = $this->get('title');
        
        if ($this->{'seo.page_title'})
        {
            $return = $this->{'seo.page_title'};
        }
        
        return $return;
    }

    public function seoDescription()
    {
        if ($this->{'seo.meta_description'})
        {
            $return = $this->{'seo.meta_description'};
        }
        elseif ($this->get('description'))
        {
            $return = $this->get('description');
        }
        else
        {
            $return = $this->get('copy');
        }
        
        if (empty($return)) 
        {
            return $return;
        }
            
        $return = \Dsc\System::instance()->get('outputfilter')->cleanText($return);
        
        $return = $this->seoShortenInput( $return );
        
        return $return;
    }

    public function seoShortenInput($input, $length = 160)
    {
        if (strlen($input) <= $length)
        {
            return $input;
        }
        
        $last_space = strrpos(substr($input, 0, $length), ' ');
        $trimmed_text = substr($input, 0, $last_space);
        
        return $trimmed_text;
    }
}