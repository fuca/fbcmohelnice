<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\Event,	
    florbalMohelnice\Entities\Comment,
    florbalMohelnice\Forms\EventForm,
    florbalMohelnice\Models\GroupsModel,
    florbalMohelnice\Models\UserModel;

/**
 * Description of EventsModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class EventsModel extends BaseModel implements \florbalMohelnice\Models\ICommentableModel {

    /**
     *
     */
    public function getFluent() {
	return $this->connection->select('*')->from('Events');
    }
    
    public function getUserParticipatedEventsFluent($kid, $part) {
	if (!is_numeric($kid))
	    throw new \InvalidArgumentException("Argument kid has to be type of numeric, '$kid'given");
	if (!is_numeric($part))
	    throw new \InvalidArgumentException("Argument part has to be type of numeric, '$part' given");
	try {
	    $res = $this->connection->select("DISTINCT Events.*")
		    ->from("Events")
		    ->join('relEventGroup')->using('(id_event)')
		    //->join('Groups')->using('(id_group)')
		    //->join('Users')->on('Users.kid = ordered_kid')
		    ->join('relParticipation')->using('(id_event)')
		    ->where('relParticipation.kid = %i AND relParticipation.participation = %i', $kid, $part);
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $res;
    }

    /**
     *
     */
    public function getAllEvents() {
	try {
	    $res = $this->connection->select('
			    Events.*,
			    CONCAT(surname, \'  \', name, \' (\',kid,\')\') AS author,
			    EventTypes.title AS type')
		    ->from('Events')
		    ->join('Users')->on('kid = ordered_kid')
		    ->join('EventTypes')->on('event_type = id_event_type')
		    ->execute()->setRowClass('florbalMohelnice\Entities\Event')
		    ->fetchAll();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }

    public function getAllEventsByCategory(array $abbrs = NULL, $fluent = FALSE) {
	try {
	    $res = $this->connection->select('
			    Events.*,
			    CONCAT(surname, \'  \', Users.name, \' (\',Users.kid,\')\') AS author,
			    EventTypes.title AS type')->setFlag('distinct')
			    ->from('Events')
			    ->leftJoin('Users')->on('kid = ordered_kid')
			    ->join('EventTypes')->on('event_type = id_event_type')
			    ->leftJoin('relEventGroup')->using('(id_event)')
			    ->leftJoin('Groups')->using('(id_group)');
	    
	    if ($abbrs !== NULL)
		foreach($abbrs as $abbr) {
		    if ($abbr != 'fbc')
			$res->where('Groups.abbr = %s', $abbr);
		}

	    $res->orderBy('take_place_from');
	    if (!$fluent)
		$res->execute()->setRowClass('florbalMohelnice\Entities\Event')
		    ->fetchAll();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }

    /**
     *
     */
    public function getAdminGridEvents($kid = FALSE) {
	if (!is_numeric($kid) && $kid)
	    throw new \Nette\InvalidArgumentException('Argument has to be type of numeric');
    }

    /**
     * Returns event associated with given id
     * @param numeric
     */
    public function getEvent($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException("Argument ID has to be a type of numeric, ". $id ." given");

	$gModel = new GroupsModel($this->connection);
	try {
	    $inner = $this->connection
			    ->select('*')
			    ->from('Events')->where('id_event = %i', $id);

	    $ev = $this->connection->select('
				event.*, 
				EventTypes.title AS event_type_title, 
				CONCAT(surname, \'  \', name, \' (\',kid,\')\') AS author')
		    ->from($inner)->as('event')
		    ->join('EventTypes')->on('event_type = id_event_type')
		    ->join('Users')->on('ordered_kid = kid')
		    ->execute()->setRowClass('florbalMohelnice\Entities\Event')
		    ->fetch();

	    /* 			$parties = $this->connection
	      ->select('
	      relParticipation.*,
	      CONCAT(surname, \' \', name, \' (\',kid,\')\') AS author')
	      ->from('relParticipation')
	      ->join('Users')->using('(kid)')
	      ->where('id_event = %i', $id)
	      ->execute()->fetchAll(); */
	    if ($ev !== FALSE) {
		$groups = $gModel->getEventGroups($ev);
		$ev->offsetSet('categories', $groups);
		/* 				$ev->offsetSet('parties', $parties); */
	    }
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $ev;
    }

    /**
     *
     */
    public function updateEvent(Event $ev) {
	$cats = $ev->offsetGet('categories');
	$ev->offsetUnset('categories');
	/* 		$parties = $ev->offsetGet('parties');
	  $ev->offsetUnset('parties'); */
	$gModel = new GroupsModel($this->connection);

	try {
	    $this->begin();
	    $this->connection->update('Events', $ev)
		    ->where('id_event = %i', $ev->id_event)
		    ->execute();
	    $ev->offsetSet('categories', $cats);
	    $gModel->removeEventGroups($ev);
	    $gModel->addEventGroups($ev);
	    /* 			$this->removeEventParties($ev);
	      $ev->offsetSet('parties', $parties);
	      $this->addEventParties($ev, $parties); */
	    $this->commit();
	} catch (DibiException $ex) {
	    $this->rollback();
	    throw new \Nette\IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    private function removeEventParties(Event $ev) {
	$this->connection->delete('relParticipation')
		->where('id_event = %i', $ev->id_event)
		->execute();
    }

    /**
     *
     */
    private function addEventParties(Event $ev) { // act like you cant see that
	foreach ($ev->parties as $pa) {
	    if ($pa['user'] == EventForm::ALL_USERS) {
		$uModel = new UserModel($this->connection);
		$users = $uModel->getSelectUsers();
		$fakeParty = array();
		foreach ($users as $key => $u) {
		    $fakeParty += array('user' => $key, 'comment' => '*');
		}
		$ev->offsetSet('parties', $fakeParty);
		$this->addEventParties($ev);
		break;
	    }
	    $pData = array(
		'kid' => $pa['user'],
		'id_event' => $ev->id_event,
		'participation' => $ev->participation_mode,
		'comment' => $pa['comment']);
	    $this->connection->insert('relParticipation', $pData)
		    ->execute();
	}
    }

    /**
     *
     */
    public function createEvent(Event $ev) {
	$gModel = new GroupsModel($this->connection);
	$cats = $ev->offsetGet('categories');
	$ev->offsetUnset('categories');
	/* 		$parties = $ev->offsetGet('parties');
	  $ev->offsetUnset('parties'); */
	try {
	    $this->begin();
	    $this->connection->insert('Events', $ev)
		    ->execute();
	    $evId = $this->connection->insertId();
	    $ev->offsetSet('categories', $cats);
	    $ev->offsetSet('id_event', $evId);
	    $gModel->addEventGroups($ev);
	    /* 			$ev->offsetSet('parties', $parties);
	      $this->addEventParties($ev); */
	    $this->commit();
	} catch (DibiException $ex) {
	    $this->rollback();
	    throw new \Nette\IOException($ex->getMessage);
	}
    }

    /**
     *
     */
    public function deleteEvent($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException("Argument id has to be type of numeric");

	$gpM = new GroupsModel($this->connection);
	try {
	    $this->begin();
	    
	    $event = $this->getEvent($id);
	    if ($event !== FALSE) 
		$gpM->removeEventGroups($event);
	    else 
		throw new \DibiException("Event with id $id does not exist");
	    
	    $this->connection->delete('Events')
		    ->where('id_event = %i', $id)
		    ->execute();
	    $this->connection->delete('relParticipation')
		    ->where('id_event = %i', $id)
		    ->execute();
	    $this->commit();
	} catch (DibiException $ex) {
	    $this->rollback();
	    throw new IOException($ex->getMessage());
	}
    }

    /**
     *
     */
    private function getTypesFluent() {
	return $this->connection->select('*')->from('EventTypes');
    }

    /**
     *
     */
    public function getSelectTypes() {
	try {
	    $res = $this->getTypesFluent()
			    ->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	}
	return $res;
    }
    
        // ----------------- ICommentableModel -------------------

    /**
     *
     */
    public function createComment(Comment $c) {
	$com = $c;
	$com->offsetUnset('id_comment');
	try {
	    $this->connection->insert('Comments', $c)->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
    }

    /**
     *
     */
    public function updateComment(Comment $c) {
	$c_type = $c->offsetGet('relation_mode');
	$c->offsetUnset('relation_mode');
	$c_id = $c->offsetGet('relate_post');
	$c->offsetUnset('relate_post');
	try {
	    $this->connection
		    ->update('Comments', $c)
		    ->where('id_comment = %i', $c_id)
		    ->execute();
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
    }

    /**
     *
     */
    public function deleteComment(Comment $c) {
	//
    }

    /**
     *
     */
    public function getCommentsFluent($id) {
	try {
	    $res = $this->connection->select('
									Comments.*, 
									CONCAT(surname, \' \', Users.name, \' (\', kid, \')\') AS author')
		    ->from('Comments')
		    ->leftJoin('Users')->using('(kid)')
		    ->where('relate_post = %i AND relation_mode = %s', (integer) $id, \BasePresenter::C_EVENT_TYPE);
	} catch (DibiException $ex) {
	    throw new IOException($ex->getMessage());
	    return;
	}
	return $res;
    }

}
