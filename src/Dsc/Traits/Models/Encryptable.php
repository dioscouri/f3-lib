<?php

namespace Dsc\Traits\Models;

/**
 * This trait handles encryption of data on model level
 */
trait Encryptable{
	
	/**
	 * List of field to be encrypted should be procided in model's config array like:
	 * 
	 * protected $__config = array(
	 *	 'encrypted_fields' => array(
	 *		'email', 'zip'
	 *	 )
	 * );
	 */
	use\Dsc\Traits\Encryptable;
	
	/**
	 * Change: encrypt filtering value if it filters on an encrypted fields
	 *
	 * @return array
	 */
	public function conditions()
	{
        if (empty($this->__query_params['conditions'])) {
        	$this->fetchConditions();
        }
		if( !empty( $this->__config['encrypted_fields'] ) ){
			$encrypted_fields = $this->__config['encrypted_fields'];
			if( is_array( $encrypted_fields ) === false ) {
				$encrypted_fields = array( $encrypted_fields );
			}
			foreach ($encrypted_fields as $field)
			{
				$filter_field = $this->getState('filter.' . $field);
				if (!empty($filter_field) )
				{
					$encrypted = $this->encryptTextBase64($filter_field);
					$this->setCondition($field, $encrypted);
				}
			}
		}
		
		return $this->__query_params['conditions'];
	}
	
	/**
	 * Change: decrypt encrypted fields
	 *
	 * @return multitype:\Dsc\Traits\ModelEncryptable
	 */
	protected function fetchItems()
	{
		$this->__cursor = $this->collection()->find($this->conditions(), $this->fields());
	
		if ($this->getParam('sort'))
		{
			$this->__cursor->sort($this->getParam('sort'));
		}
		if ($this->getParam('limit'))
		{
			$this->__cursor->limit($this->getParam('limit'));
		}
		if ($this->getParam('skip'))
		{
			$this->__cursor->skip($this->getParam('skip'));
		}
	
		$items = array();
		foreach ($this->__cursor as $doc)
		{
			$item = new static($doc);
			$this->decryptFieldsModel( $item );
			$items[] = $item;
		}
	
		return $items;
	}
	
	private function decryptFieldsModel(&$model){
		if( !empty( $this->__config['encrypted_fields'] ) ){
			$encrypted_fields = $this->__config['encrypted_fields'];
			if( is_array( $encrypted_fields ) === false ) {
				$encrypted_fields = array( $encrypted_fields );
			}
			
			foreach ($encrypted_fields as $field)
			{
				if( strlen( $model->$field ) ) {
					$model->$field = $this->decryptTextBase64( $model->$field );
				}
			}
		}
	}

	
	/*
	 * This method encrypts fields in provided array
	 * 
	 * @param $arr		Array with field to be encrypted
	 * 
	 * @return	Array with encrypted fields
	 */
	private function encryptFieldsModel($arr){
		if( !empty( $this->__config['encrypted_fields'] ) ){
			$encrypted_fields = $this->__config['encrypted_fields'];
			if( is_array( $encrypted_fields ) === false ) {
				$encrypted_fields = array( $encrypted_fields );
			}
				
			foreach ($encrypted_fields as $field)
			{
				$field_text = \Dsc\ArrayHelper::get( $arr, $field );
				if( !is_null( $field_text ) ) {
					\Dsc\ArrayHelper::set( $arr, $field, $this->encryptTextBase64( $field_text ) );
				}
			}
		}
		return $arr;
	}
	
	/**
	 * Change: decrypt encrypted fields
	 *
	 * @return Ambigous <NULL, \Dsc\Traits\ModelEncryptable>
	 */
	protected function fetchItem()
	{
		$this->__cursor = $this->collection()->find($this->conditions(), $this->fields());
		
		if ($this->getParam('sort'))
		{
			$this->__cursor->sort($this->getParam('sort'));
		}
		$this->__cursor->limit(1);
		$this->__cursor->skip(0);
	
		$item = null;
		if ($this->__cursor->hasNext())
		{
			$item = new static($this->__cursor->getNext());
			$this->decryptFieldsModel( $item );
		}
	
		return $item;
	}
	
	/**
	 * Change: encrypt fields
	 *
	 * @param unknown $document
	 * @param unknown $options
	 * @return \Dsc\Mongo\Collection
	 */
	public function insert($document = array(), $options = array())
	{
		$this->__options = $options;
		
		$this->bind($document, $options);
		
		$this->beforeValidate();
		$this->validate();
		$this->beforeCreate();
		$this->beforeSave();
	
		if (! $this->get('id'))
		{
			$this->set('_id', new \MongoId());
		}
	
		$doc = $this->cast();
		$array = $this->encryptFieldsModel( $doc );
		
		if ($this->__last_operation = $this->collection()->insert($array))
		{
			$this->set('_id', $this->__doc['_id']);
		}
	
		$this->afterCreate();
		$this->afterSave();

		return $this;
	}
	
	/**
	 * Change: encrypt fields
	 *
	 * @param unknown $document
	 * @param unknown $options
	 */
	public function update($document = array(), $options = array())
	{
		$this->__options = $options;
	
		if (! isset($options['overwrite']) || $options['overwrite'] === true)
		{
			return $this->overwrite($document, $options);
		}
		
		$this->beforeUpdate();
		$this->beforeSave();
		$document = $this->encryptFieldsModel( $document );
		
		// otherwise do a selective update with $set = array() and multi=false
		$this->__last_operation = $this->collection()->update(array(
				'_id' => new \MongoId((string) $this->get('id'))
		), array(
				'$set' => $array
		), array(
				'multiple' => false
		));
	
		$this->afterUpdate();
		$this->afterSave();
	
		return $this->lastOperation();
	}
	
	/**
	 * Change: encrypt fields
	 *
	 * @param unknown $document
	 * @param unknown $options
	 * @return \Dsc\Mongo\Collection
	 */
	public function overwrite($document = array(), $options = array())
	{
		$this->__options = $options;
		$this->bind($document, $options);
	
		$this->beforeValidate();
		$this->validate();
		$this->beforeUpdate();
		$this->beforeSave();
	
		$doc = $this->cast();
		$arr = $this->encryptFieldsModel( $doc );
		$this->__last_operation = $this->collection()->update(array(
				'_id' => new \MongoId((string) $this->get('id'))
		), $arr, array(
				'upsert' => true,
				'multiple' => false
		));
		
		$this->afterUpdate();
		$this->afterSave();
	
		return $this;
	}
}