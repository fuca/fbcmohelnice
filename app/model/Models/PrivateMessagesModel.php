<?php
namespace florbalMohelnice\Models;
use \florbalMohelnice\Entities\PrivateMessage,
	\DibiFluent;

/**
 * Description of PrivateMessageModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class PrivateMessagesModel extends BaseModel {
	
	/**
	 * Creates basic fluent for messages and sender name
	 * @return DibiFluent
	 */
	private function _getFluentBase() {
		$innerQ = $this->connection
						->select('
								id, 
								mailbox_kid, 
								sender_kid, 
								recipient_kid, 
								id_message, 
								subject, 
								content, 
								sent, 
								status, 
								starred, 
								CONCAT(name ,\' \', surname,\' \', \'(\',kid,\')\') AS sender_name ')
						->from('relMailbox')
						->join('PrivateMessages')->using('(id_message)')
						->join('Users')->on('sender_kid = kid');
		return $innerQ;
	}
	
	/**
	 *
	 */
	 private function _getFluentOuter(DibiFluent $base) {
	 	return $this->connection
						->select('
								id, 
								mailbox_kid, 
								sender_kid,
								recipient_kid, 
								id_message, 
								subject, 
								content, 
								sent, 
								status, 
								starred, 
								CONCAT(name ,\' \', surname,\' \', \'(\',kid,\')\') AS recipient_name, 									sender_name')
						->from($base)->as('senderNMessages')
						->join('Users')->on('recipient_kid = kid');
	 }

	/**
	 * Method for obtain fluent for all messages
	 * @return DibiFluent
	 */
	public function getFluent() {
		return $this->_getFluentOuter($this->_getFluentBase());
	}
	
	/**
	 * Get single message
	 * @param numeric
	 * @return PrivateMessage
	 */
	 public function getMessage($id) {
	 	try {
	 		$innerQ = $this->_getFluentBase()->where('id = %i', $id);
	 		$res = $this->_getFluentOuter($innerQ)->execute()
	 						->setRowClass('florbalMohelnice\Entities\PrivateMessage')
	 						->fetch();

	 		$recps = $this->getRecipients($res);
	 	} catch (DibiException $ex) {
	 		throw new \Nette\IOException($ex->getMessage());
	 	}
	 	$res->offsetSet('recipients', $recps);
	 	return $res;
	 }
	
	/**
	 * Creates inbox fluent
	 * @param numeric
	 * @return DibiFluent
	 */
	public function getInboxFluent($kid) {
		if (!is_numeric($kid)) 
			throw new \Nette\InvalidArgumentException("Argument has to be type of numeric");
		$innerQ = $this->_getFluentBase()
				->where('mailbox_kid = recipient_kid AND mailbox_kid = %i AND status != %s', $kid, \florbalMohelnice\Entities\PrivateMessage::STATUS_DELETED);
		$foldedByOuter = $this->_getFluentOuter($innerQ);
		return $foldedByOuter;
	}
	
	/**
	 * Returns count of unread messages
	 * @param type $kid
	 * @return type
	 * @throws \Nette\InvalidArgumentException
	 * @throws Exception
	 */
	public function getUnreadCount($kid) {
	    if (!is_numeric($kid)) 
			throw new \Nette\InvalidArgumentException("Argument has to be type of numeric");
	    try {
		return $this->connection->select('COUNT(*)')
			->from("relMailbox")
			->where('mailbox_kid = %s AND recipient_kid = %i AND status = %s', $kid, $kid, \florbalMohelnice\Entities\PrivateMessage::STATUS_UNREAD)
			->execute()->fetchSingle();
	    } catch(DibiException $ex) {
		throw new Exception($ex);
	    }
	}
	
	/**
	 * Creates outbox fluent
	 * @param numeric
	 * @return DibiFluent
	 */
	public function getOutboxFluent($kid) {
		if (!is_numeric($kid)) 
			throw new \Nette\InvalidArgumentException("Second argument has to be type of numeric");
		$innerQ = $this->_getFluentBase()
				->where('mailbox_kid = sender_kid AND mailbox_kid = %i AND status != %s', $kid, \florbalMohelnice\Entities\PrivateMessage::STATUS_DELETED);
		return $this->_getFluentOuter($innerQ);
	}
	
	/**
	 * Creates deleted messages fluent
	 * @param numeric
	 * @return DibiFluent
	 */
	public function getDeletedFluent($kid) {
		if (!is_numeric($kid)) 
			throw new \Nette\InvalidArgumentException("Second argument has to be type of numeric");
		$innerQ = $this->_getFluentBase()
					->where('mailbox_kid = %i AND status = %s', $kid, PrivateMessage::STATUS_DELETED);
		return $this->_getFluentOuter($innerQ);
	}

	/**
	 * Returns all recipients of message 
	 * @return Array of DibiRow
	 */
	public function getRecipients(PrivateMessage $m, $fluent = FALSE) {
		$pmId = $m->offsetGet('id_message');
		$res = $this->connection
						->select('recipient_kid, CONCAT(surname,\' \', name, \' (\',kid,\')\') AS recipient')
				 	 	->from('relMailbox')
				 	 	->where('id_message = %i AND mailbox_kid != sender_kid', $pmId)
				 	 	->join('Users')->on('kid = recipient_kid');
		if (!$fluent) {
			try {
				$res = $res->execute()->fetchPairs();
			} catch (DibiException $ex) {
				throw new IOException($ex->getMessage());
			}
		}
		return $res;
	}
	
	/**
	 * Add new message to database
	 * @return new message id
	 */
	public function createMessage(\florbalMohelnice\Entities\PrivateMessage $pm, $kid) {
		if (!is_numeric($kid)) 
			throw new \Nette\InvalidArgumentException('Second argument has to be a type of numeric');
			
		$pmId = FALSE;
		$recipients = $pm->offsetGet('recipients');
		$pm->offsetUnset('recipients');
		
		$list_of_recipients = implode(' ', $recipients);
		
		try {
			$this->connection->insert('PrivateMessages', $pm->toArray())->execute();
			$pmId = $this->connection->insertId();
		
			// TODO positive log?
		
			foreach ($recipients as $r) {
				// ADD TO RECIPIENT INBOX
				$relMailbox = new \DibiRow(array());
				$relMailbox->offsetSet('mailbox_kid', $r);
				$relMailbox->offsetSet('id_message', $pmId);
//				$relMailbox->offsetSet('list_of_recipients', $list_of_recipients);
				$relMailbox->offsetSet('sender_kid', $kid);
				$relMailbox->offsetSet('recipient_kid', $r);
				$this->connection->insert('relMailbox', $relMailbox)->execute();
				// ADD TO SENDER INBOX 
				$relMailbox->offsetSet('mailbox_kid', $kid);
				$this->connection->insert('relMailbox', $relMailbox)->execute();
			}
		} catch (DibiException $ex) {
			throw new \Nette\IOException("Zpráva nemohla být uložena. -- ".$ex->getMessage());
			// TODO logger
		}
		return $pmId;
	}
	
	/**
	 *
	 */
	public function starMessage(PrivateMessage $m) {
		$m->offsetUnset(PrivateMessage::IDENTIFIER);
		$m->offsetUnset('mailbox_kid');
		$id = $m->offsetGet('id_entry');
		$m->offsetUnset('id_entry');
		$m->offsetSet('starred', !$m->offsetGet('starred'));
		try {
			$this->connection->update('relMailbox', $m->toArray())
								->where('id = %i', $id)
				    ->execute();
		} catch (DibiException $x) {
			throw new \Nette\IOException($x->getMessage());
		}
	}
	
	/**
	 * 
	 * @param \florbalMohelnice\Entities\PrivateMessage $m
	 * @throws \Nette\IOException
	 */
	public function markAsUnread(PrivateMessage $m) {
		$m->offsetUnset(PrivateMessage::IDENTIFIER);
		$m->offsetUnset('mailbox_kid');
		$id = $m->offsetGet('id_entry');
		$m->offsetUnset('id_entry');
		dump($m);
		$ss = $m->offsetGet('status');
		$m->offsetSet('status', $ss == PrivateMessage::STATUS_READ || PrivateMessage::STATUS_DELETED? PrivateMessage::STATUS_UNREAD:$ss);
		try {
			$this->connection->update('relMailbox', $m->toArray())
								->where('id = %i', $id)
				    ->execute();
		} catch (DibiException $x) {
			throw new \Nette\IOException($x->getMessage());
		}
	}
	
	/**
	 * Moves message to deleted
	 * @param numeric
	 */
	public function deleteMessage($id) {
		if (!is_numeric($id)) 
			throw new \Nette\InvalidArgumentException('Argument has to be a type of numeric');
		try {
			$this->connection->update('relMailbox', array('status'=>PrivateMessage::STATUS_DELETED))
								->where('id = %i', $id)->execute();
		} catch (DibiException $ex) {
			throw new \Nette\IOException($ex->getMessage());
		}		
	}
	
	/**
	 * Destroy message from database
	 * @param numeric
	 */
	public function dropMessage($id) {
		if (!is_numeric($id)) 
			throw new \Nette\InvalidArgumentException('Argument has to be a type of numeric');
		try {
			$this->connection->delete('relMailbox')->where('id = %i', $id)->execute();
		} catch(DibiException $ex) {
			throw new \Nette\IOException($ex->getMessage());
		}
	}
	
	/**
	 * Marks message as read or as argument $status contains
	 * @param \florbalMohelnice\Entities\PrivateMessage $m
	 * @param type $status
	 * @throws \Nette\IOException
	 */
	public function markAs(PrivateMessage $m, $status = NULL) {
		try {
		    $this->connection->update('relMailbox', 
			    array("status"=>($status == NULL ? PrivateMessage::STATUS_READ:$status)))
						 ->where('id = %i', $m->offsetGet('id'))->execute();
		} catch (DibiException $x) {
			throw new \Nette\IOException($x->getMessage());
		}
	}
}

