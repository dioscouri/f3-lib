<?php
namespace Dsc\Traits\Models;

trait Voting
{

    public $voting = array(
    );

    public function votingAcceptVote($vote, $user = null)
    {
       if(!empty($user) && $user instanceof \Users\Models\Users ) {
       	//check the votes collection for this same vote
       	$db =  \Dsc\System::instance()->get('mongo');
       	$collection = $db->selectCollection('voting.votes');
       	$doc = $collection->findOne(array('object_id' => $this->_id, 'user_id' => $user->id));
       	
       	if(empty($doc)) {
       		//accept the vote
       		$collection->insert(array('object_id' => $this->_id, 'user_id' => $user->id, 'vote' => $vote, "time" => new \MongoDate()   ));
       	} else {
       		throw new \Exception('User has already voted');
       	}
       	
       }
       
       //update the document vote counts
       $votes = $this->votes;
       if(!empty($votes[$vote])) {
       	$votes[$vote] = (int) $votes[$vote] + 1;
       } else {
       	$votes[$vote] = (int) 1;
       }
       $this->set('votes', $votes)->store();
    	
    	
       return $votes;
    	
    }
    //TODO this
    public function votingRemoveVote($vote, $user = null)
    {
    	/*if(!empty($user) && $user instanceof \Users\Models\Users ) {
    		//check the votes collection for this same vote
    		$db =  \Dsc\System::instance()->get('mongo');
    		$collection = $db->selectCollection('voting.votes');
    		$doc = $collection->findOne(array('object_id' => $this->_id, 'user_id' => $user->id));
    
    		if(empty($doc)) {
    			//accept the vote
    			$collection->insert(array('object_id' => $this->_id, 'user_id' => $user->id, 'vote' => $vote, "time" => new \MongoDate()   ));
    		} else {
    			throw new \Exception('User has already voted');
    		}
    
    	}
    	 
    	//update the document vote counts
    	$votes = $this->votes;
    	if(!empty($votes[$vote])) {
    		$votes[$vote] = (int) $votes[$vote] + 1;
    	} else {
    		$votes[$vote] = (int) 1;
    	}
    	$this->set('votes', $votes)->store();
    	 
    	 
    	return $votes;*/
    	 
    }

   
}