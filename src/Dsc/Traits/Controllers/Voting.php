<?php
namespace Dsc\Traits\Controllers;

trait Voting
{
    
	var $votingRequireLogin = true;
	var $acceptableVotes = array();
	
	
	/*
	 * This method is used to save the votes per object. 
	 * the routes will look like this
	 * 
	 * 
	 * domain.com/yourcustomapp/vote/@objectid/@vote
	 * 
	 * 
	 * It is up to your routes file to support GET|POST|AJAX etc
	 * 
	 */
	public function votingSaveVote() {
		
		$user = null;
		try {
			
		if($this->votingRequireLogin) {
			$this->requireIdentity('Please Login to register vote.');
			$user = $this->getIdentity();
		}
		
		$objectid = $this->app->get('PARAMS.objectid');
		$vote = $this->app->get('PARAMS.vote');
		
		//THIS MODEL NEEDS TO EXTEND VOTING MODEL TRAIT
		$document = $this->getModel()->setState('filter.id', $objectid)->getItem();
		
		if(empty($document->id)) {
			throw new \Exception('Document not found');
		}
		
		if(!empty($this->acceptableVotes) && is_array($this->acceptableVotes) ) {
			if(!in_array($vote,$this->acceptableVotes)) {
				throw new \Exception('Vote is not acceptable');
			}
		}
		
		
		$document->votingAcceptVote($vote, $user);
			
		
		
		} catch (\Exception $e) {
			
			
		}
		
		
		
		
		
	}
	
	
	
	
	
	
}