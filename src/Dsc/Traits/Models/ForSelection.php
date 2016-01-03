<?php
namespace Dsc\Traits\Models;

trait ForSelection
{

    public $__select2_fields = array(
        'id' => '_id',
        'text' => 'title',
        'slug' => 'slug',
        'brackets' => '',
    );

    protected function forSelectionBeforeValidate($field)
    {
        if (!empty($this->$field) && !is_array($this->$field))
        {
            $this->$field = trim($this->$field);
            if (!empty($this->$field))
            {
                $this->$field = \Base::instance()->split((string) $this->$field);
            }
        }
        elseif (empty($this->$field) && !is_array($this->$field))
        {
            $this->$field = array();
        }
    }

    /**
     * Helper method for creating select list options
     *
     * @param array $query            
     * @return multitype:multitype:string NULL
     */
    public static function forSelection(array $query = array())
    {
        $model = new static();
        if( !isset($model->__select2_fields['brackets'] ) ){
        	$model->__select2_fields['brackets'] = '';
        }
		if (empty( $model->__select2_fields['brackets'] ) ){
        $cursor = $model->collection()->find($query, [
            $model->__select2_fields['text'] => 1,
            $model->__select2_fields['id'] => 1,
            $model->__select2_fields['slug'] => 1,            
        ]);
		} else {
        $cursor = $model->collection()->find($query, [
            $model->__select2_fields['text'] => 1,
            $model->__select2_fields['id'] => 1,
            $model->__select2_fields['slug'] => 1,            
            $model->__select2_fields['brackets'] => 1,
        ]);
		}
        $cursor->sort(array(
            $model->__select2_fields['text'] => 1
        ));
        
        $result = array();
        foreach ($cursor as $doc)
        {
        	$arr = [];
			if (empty( $model->__select2_fields['brackets'] ) ){
	            $arr = [
	                'id' => (string) $doc[$model->__select2_fields['id']],
	                'text' => htmlspecialchars($doc[$model->__select2_fields['text']], ENT_QUOTES)
	            ];
			} else {
	            $arr = [
	                'id' => (string) $doc[$model->__select2_fields['id']],
	                'text' => htmlspecialchars($doc[$model->__select2_fields['text']], ENT_QUOTES).' ('.htmlspecialchars($doc[$model->__select2_fields['brackets']], ENT_QUOTES).')'
	            ];
			}
            $result[] = $arr;
        }
        
        return $result;
    }
}