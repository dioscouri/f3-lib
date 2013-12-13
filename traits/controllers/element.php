<?php
namespace Dsc\Traits\Controllers;

/**
 * Adds methods to controller class that simplify using it as an element generator
 */
trait Element
{
    abstract public function element();

    /**
     * These MUST be defined in your controller.
     * Here is a typical format.
     *
     protected $element_item_key = 'id'; // returns the property used to get the value from the element object.  If you want the $item->id, this method should return "id"
     protected $element_item_title_key = 'title'; // returns the property used to get the title from the element object.  If you want the $item->title, this method should return "title"
     protected $element_url = '/admin/assets/element/{id}'; // where {id} will be replaced by the id of the element object
     */
        
    /**
     * When provided a value, return the $title that should be displayed in the element input
     * 
     * If $value=null, return a default, such as "Select an XXXXX"
     */
    abstract protected function getElementItemTitle($value=null); // 
    
    protected function getElementUrl()
    {
        if (empty($this->element_url)) {
            throw new \Exception('Must define element_url');
        }
        
        return $this->element_url;
    }
    
    protected function getElementItemKey()
    {
        if (empty($this->element_item_key)) {
            throw new \Exception('Must define element_item_key');
        }
    
        return $this->element_item_key;
    }
    
    protected function getElementItemTitleKey()
    {
        if (empty($this->element_item_title_key)) {
            throw new \Exception('Must define element_item_title_key');
        }
    
        return $this->element_item_title_key;
    }
    
    protected function getElementSelectFunction()
    {
        $select_function_name = $this->inputfilter->clean(strtolower( get_class() ), 'ALNUM');
        
        return $select_function_name;
    }
    
    public function fetchElement($id, $value=null, $options=array() )
    {
        $f3 = \Base::instance();
        $html_pieces = array();
        
        $title = $this->getElementItemTitle($value);        
                
        // possible keys in the $options array:
        // $url = the url opened in the lightbox
        // $field = a name for the input field (if different from $id)        
        // $onclick_select = custom js to execute after user selects and item
        // $select_string = custom string to use for the "Select" button
        // $onclick_reset = custom js to execute after user resets the element
        // $reset_string = custom string for the "Reset" button                
        
        $select_function_name = $this->getElementSelectFunction();
        $reset_function_name = $this->inputfilter->clean(strtolower( get_class() ), 'ALNUM') . "_reset";

        $url = $f3->get('BASE') . str_replace('{id}', $id, $this->getElementUrl());
        if (!empty($options['url']))
        {
            $url = $options['url'];
        }
        
        $field = $id;
        if (!empty($options['field']))
        {
            $field = $options['field'];
        }
        
        $close_command = "jQuery.colorbox.close();";
        if (!empty($options['close_command']))
        {
            $close_command = $options['close_command'];
        }
        
        $select_string = $this->getElementItemTitle();
        if (!empty($options['select_string']))
        {
            $select_string = $options['select_string'];
        }
        
        $reset_string = 'Reset';
        if (!empty($options['reset_string']))
        {
            $reset_string = $options['reset_string'];
        }
                
        $onclick_select = null;
        if (!empty($options['onclick_select']))
        {
            $onclick_select = $options['onclick_select'];
        }
        
        $onclick_reset = null;
        if (!empty($options['onclick_reset']))
        {
            $onclick_reset = $options['onclick_reset'];
        }
                
        $html_pieces[] = "
        <script>"  .  $select_function_name  .  " = function(value, title, object) {
        document.getElementById(object + '_id').value = value;
        document.getElementById(object + '_name').value = title;
        document.getElementById(object + '_name_hidden').value = title;
        $close_command
        $onclick_select
        }</script>";
        
        $html_pieces[] = "
        <script>" . $reset_function_name . " = function(value, title, object) {
        document.getElementById(object + '_id').value = value;
        document.getElementById(object + '_name').value = title;
        $onclick_reset
        }</script>";

        // TODO assume colorbox is included? or include it here?
        $html_pieces[] = '<div id="' . $id . '_primary" class="input-group">';
        $html_pieces[] = '<input class="form-control" type="text" id="' . $id . '_name" value="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" disabled="disabled" />';
        $html_pieces[] = '<span class="input-group-btn">';
        $html_pieces[] = '<a data-colorbox="iframe" class="btn btn-primary" href="' . $url . '">'  .  $select_string  .  '</a>';
        $html_pieces[] = '<a href="javascript:void(0);" class="btn btn-danger" onclick="Dsc.executeFunctionByName(\'' . $reset_function_name . '\', window, null, \'' . $select_string . '\', \'' . $id . '\' );">' . $reset_string . '</a>';
        $html_pieces[] = '</span>';
        $html_pieces[] = '<input type="hidden" id="' . $id . '_id" name="' . $field . '" value="' . $value . '" />';
        $html_pieces[] = '<input type="hidden" id="' . $id . '_name_hidden" value="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" />';
        $html_pieces[] = '</div>';
        
        if (method_exists($this, 'getElementHtml')) 
        {
            $html_pieces = $this->getElementHtml( $html_pieces, $id, $value, $options );
        }
        
        $html = implode("\n", $html_pieces);
        
        return $html;
    }
}