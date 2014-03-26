<?php
namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\CreditEntry,
		\Nette\Diagnostics\Logger;

/**
 * Description of CreditModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class CreditModel extends BaseModel {
	
	public function getFluent($kid = NULL) {		
		
		$res = $this->connection->select('[id_credit],
										  [kid],
										  [surname],
										  [name],
										  SUM(credit_count) AS credit_count,
										  [CreditRewards.label] AS subject,
										  [Credits.season],
										  [ordered_time]')
					->from('Credits')->innerJoin('Users')->using('(kid)')
					->innerJoin('CreditRewards')->on('subject = CreditRewards.id')->where('Users.activity = %s',"act");
		if ($kid !== NULL) $res->where('Users.kid = %i AND Users.activity = %s', $kid, "act");

		$res->orderBy('Credits.season')->desc()->groupBy('Users.kid');
		return $res;
	}
	
	public function getDetailFLuent($kid = NULL) {
		$res = $this->connection->select('[id_credit],
										  [kid],
										  [surname],
										  [name],
										  [credit_count],
										  [CreditRewards.label] AS subject,
										  [Credits.season],
										  [ordered_time]')
					->from('Credits')->innerJoin('Users')->using('(kid)')
					->innerJoin('CreditRewards')->on('subject = CreditRewards.id');
		if ($kid !== NULL) $res->where('Users.kid = %i', $kid);
		$res->orderBy('Credits.season')->desc();
		return $res;
		
	}
	
	public function getEntry($id) {
		$res = null;
		try {
		$res = $this->connection->select('*')->from('Credits')
				->where('id_credit = %i', $id)
				->execute()->fetch();
		if ($res === FALSE) throw new \Nette\ArgumentOutOfRangeException("Zaznam #$id nenalezen","error");
		} catch (DibiException $ex) {
			//Logger::log('CreditModel::getEntry ' . $ex, Logger::ERROR);
		}
		return $res;
	}
	
	/**
	 * 
	 * @param \florbalMohelnice\Entities\CreditEntry $ce
	 * @throws \Nette\InvalidArgumentException
	 */
	private function checkEntry(CreditEntry $ce) {
		foreach ($ce->toArray() as $key=>$el) {
			if ($el===NULL || $el==="" || $el===0 && $el != "comment") { // DO NOT LOOK AT THIS PIECE OF SHIT
				// TODO 
				//Logger::log("Attempt to save invalid creditEntry to database", Logger::ERROR);
				throw new \Nette\InvalidArgumentException("Snazite se ulozit nekompletni zaznam o kreditech. $key = $el");
			}
		}
	}
	
	public function createCreditEntry(CreditEntry $ce) {
		$ce->offsetUnset('id_credit');
		$this->checkEntry($ce);
		
		$id = $ce->offsetGet('subject');
		$value = $this->connection->select('price')->from('CreditRewards')
				->where('id = %s', $id)->execute()->fetchSingle();
		$ce->offsetSet('credit_count', $value);
		
		$insertId = null;
		try {
			$this->connection->insert('Credits', $ce->toArray())
					->execute();
			$insertId = $this->connection->insertId();
		} catch (DibiException $ex) {
			// TODO
			//Logger::log("CreateCreditEntry ".$ex, Logger::WARNING);
		} catch (\Nette\InvalidArgumentException $ex) {
			// TODO
			//Logger::log("CreateCreditEntry ".$ex, Logger::ERROR);
		}
		return $insertId;
	}
	
	public function updateCreditEntry(CreditEntry $ce) {
		$this->checkEntry($ce);
		$id = $ce->offsetGet('id_credit');
		$ce->offsetUnset('id_credit');
		$amount = $this->connection->select('price')->from('CreditRewards')
				->where('id = %s', $ce->offsetGet('subject'))
				->execute()->fetchSingle();
		$ce->offsetSet('credit_count', $amount);
		return $this->connection->update('Credits', $ce->toArray())->where('id_credit = %i', $id)
				->execute();
	}
	
	public function removeCreditEntry($id_credit) {
		// TODO zjistit jestli vubec existuje?
		
		$res = $this->connection->delete('Credits')
				->where('id_credit = %i', $id_credit)->execute();
		
		if ($res == 0) throw new \Nette\ArgumentOutOfRangeException("Zaznam #$id_credit nebyl nalezen", 'error');
	}
	
	public function getRewardsSelect() {
		
		$res = $this->connection->select('[id],[label]')
				->from('[CreditRewards]')->orderBy('label')
				->execute()->fetchPairs();
		return $res;
	}
	
	public function getUserCredit($kid) {
		if (!is_numeric($kid)) throw new \Nette\InvalidArgumentException("Argument has to be numeric");
		
		$res = $this->connection->select('id_credit,Credits.season,credit_count,ordered_time,ordered_kid,label,Credits.comment')->from('Credits')->innerJoin('CreditRewards')
				->on('CreditRewards.id = Credits.subject')->where('kid = %i', $kid)
				->execute()->fetchAll();
		$count = 0;
		
		foreach($res as $e) {
			$count += $e->credit_count;
		}
		return array('total'=>$count,'data'=>$res);
	}
	
}