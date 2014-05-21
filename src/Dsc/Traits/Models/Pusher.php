<?php

namespace Dsc\Traits\Models;

/**
 * This trait adds handy functions for dumping data for pusher
 */
trait Pusher {
	
	/* example : 
	 $pusher = new \Pusher($experience->{'pusher.public'}, $experience->{'pusher.private'}, $experience->{'pusher.app_id'});
      $data = array('tag' => (array) $tag->cast(), 'attendee' => (array) $attendee->push());
      $pusher->trigger($experience->{'pusher.channel'}, 'play', $data); 
     */

	/**
	 * List of field to be pushed should be procided in model's config array like:
	 * 
	 * protected $__config = array(
	 *	 'pusher_fields' => array(
	 *		'first_name', 'last_name'
	 *	 )
	 * );
	 */
	
	/**
	 * fields from document to be casted
	 *
	 * @return array
	 */
	
	public function push($pusher_fields = null)
	{
        if(empty($pusher_fields)) {
	        if( !empty( $this->__config['pusher_fields'] ) ){
				$pusher_fields = $this->__config['pusher_fields'];
				if( is_array( $pusher_fields ) === false ) {
					$pusher_fields = array( $pusher_fields );
				}
			}	
        } 
		
		$pusher = array();
		foreach ($pusher_fields as $field)	{	
				$pusher[$field] = $this->get($field);
		}

		return $pusher;
	}
	
	
}