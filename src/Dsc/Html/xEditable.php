<?php
namespace Dsc\Html;

class xEditable extends \Dsc\Singleton
{	
	
	var $route = '';
	var $object = '';

	
	/*
	 * This simplest way to use this method is for editing a single  object
	 * 
	 * Example useage is like this 
	 * 
	 * $xEditable = new \Dsc\Html\xEditable($mapper, '/admin/app/singleton/edit/inline');
	 * ie()
	 * $xEditable = new \Dsc\Html\xEditable($item, '/admin/pages/page/edit/inline');
	 * echo $xEditable->field('title');
	 */
	
	
	public function __construct($object, $route) {
		$this->route = $route;
		$this->object = $object;
	}
	
	
	public function field($name, $type = 'text') {
		$html = '<a href="#"';
		$html .= ' class="xeditable" ';
		$html .= ' data-pk="'. $this->object->id .'" ';
		$html .= ' data-name="'. $name .'" ';
		$html .= ' data-type="'. $type .'" ';
		$html .= ' data-url="'. $this->route .'" ';
		$html .= ' > '.$this->object->{$name} .'</a>';
		return $html;
	}
	
	
	public function select($name, $value, array $options, $class = 'xeditable') {
		$html = '<a href="#"';
		$html .= ' class="'.$class.'" ';
		$html .= ' data-pk="'. $this->object->id .'" ';
		$html .= ' data-name="'. $name .'" ';
		$html .= ' data-value="'. $value .'" ';
		$html .= ' data-source="'. str_replace('"', "&quot;", json_encode($options)).'" ';
		$html .= ' data-type="select" ';
		$html .= ' data-url="'. $this->route .'" ';
		$html .= ' >'.$this->object->{$name} .'</a>';
		return $html;
	}
	
	public function date($name, $value, array $options, $class = 'xeditable') {
		$html = '<a href="#"';
		$html .= ' class="'.$class.'" ';
		$html .= ' data-pk="'. $this->object->id .'" ';
		$html .= ' data-name="'. $name .'" ';
		$html .= ' data-value="'. $value .'" ';
		$html .= ' data-source="'. str_replace('"', "&quot;", json_encode($options)).'" ';
		$html .= ' data-type="select" ';
		$html .= ' data-url="'. $this->route .'" ';
		$html .= ' >'.$this->object->{$name} .'</a>';
		return $html;
	}
	
	
	public static function singleField($pk, $name, $value = '',  $route, $type = 'text') {
		$html = '<a href="#"';
		$html .= ' class="xeditable" ';
		$html .= ' data-pk="'. $pk .'" ';
		$html .= ' data-name="'. $name .'" ';
		$html .= ' data-type="'. $type .'" ';
		$html .= ' data-url="'. $route .'" ';
		$html .= ' > '.$value .'</a>';
		return $html;
	}
	
	
	
	
	
	
 
}