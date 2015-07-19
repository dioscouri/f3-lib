<?php
namespace Dsc\Html;

class Select extends \Dsc\Singleton
{
    /*
     * $option = array( array('text' => display_text, 'value' => 'value', 'data' => array( 'key'=>value ) ) );
     */
    public static function options($options, $selected = null)
    {
        $html = '';
        if (empty($options))
        {
            return '';
        }
        
        if (!is_array($selected)) {
            $selected = array($selected);
        }
        
        if (is_array($options[0]))
        {
            foreach ($options as $option)
            {
                $html .= '<option value="';
                $html .= $option['value'];
                $html .= '"';
                if (in_array($option['value'], $selected))
                {
                    $html .= ' selected="selected" ';
                }
                if (!empty($option['data']) && is_array($option['data']))
                {
                    foreach ($option['data'] as $key => $value)
                    {
                        $html .= ' data-' . $key . '="' . $value . '" ';
                    }
                }
                $html .= '>' . $option['text'] . '</option>';
            }
        }
        else
        {
            foreach ($options as $value)
            {
                $html .= '<option value="';
                $html .= $value;
                $html .= '"';
                if (in_array($value, $selected))
                {
                    $html .= ' selected="selected" ';
                }
                $html .= '>' . ucwords($value) . '</option>';
            }
        }
        return $html;
    }
}