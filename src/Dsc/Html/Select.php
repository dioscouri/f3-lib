<?php 
namespace Dsc\Html;

class Select extends \Dsc\Singleton
{	
	/*
	$option = array(
    array('text' => display_text, 'value' => 'value', 'data' => array( 'key'=>value  ) )
	);

	*/
    public static function options($options, $selected) {
    	$html = '';
    	if(is_array($options[0])) {
    		foreach ($options as  $option) {
    			$html .= '<option value="';
    			$html .= $option['value'];	
    			$html .= '"';
    			if($option['value'] == $selected) {
    			$html .= ' selected="selected" ';	
    			}
    			if(is_array($option['data'])) {
    				foreach ($option['data'] as $key => $value) {
    				$html .= ' data-'.$key.'="'.$value.'" ';	
    				}
    			}
    			$html .= '>'.$option['text'].'</option>';
    		}

    	} else {
    		foreach ($options as  $value) {
    			$html .= '<option value="';
    			$html .= $value;	
    			$html .= '"';
    			if($value == $selected) {
    			$html .= ' selected="selected" ';	
    			}
    			$html .= '>'.$value.'</option>';
    		}
    	}
    	return $html;

    }

}