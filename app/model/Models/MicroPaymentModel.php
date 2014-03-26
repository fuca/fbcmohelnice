<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\MicroPayment;

/**
 * Description of MicroPaymentModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class MicroPaymentModel extends BaseModel {
	
	public function getFluent($kid = NULL) {
		$q = $this->connection->select(
				'[id_micropayment],
				[Users.kid],
				[surname],
				[name],
				[EligibleSeasons.label] AS season,
				[subject],
				[amount],
				[ordered_time],
				[micropayment_type],
				[MicroPayments.comment]')
				->from('MicroPayments');
		if (is_numeric($kid)) $q = $q->where('kid = %i', $kid);
				
		$q = $q->innerJoin('Users')->using('(kid)')
				->leftJoin('EligibleSeasons')->on('id_season = season');
		return $q;
	}
	
	public function createMicroPayment(MicroPayment $mp) {
		$this->connection->insert('MicroPayments', $mp->toArray())
				->execute();
		return $this->connection->insertId();
	}
	
	public function getMicroPayment($id) {
		if (!is_integer($id)) throw new \Nette\InvalidArgumentException("Argument has to be a type of integer");
		$res = $this->connection->select('*')->from('MicroPayments')->where('id_micropayment = %i', $id)->execute()->fetch();
		return $res;
	}
	
	public function updateMicroPayment(MicroPayment $mp) {
		if (!isset($mp['id_micropayment'])) throw new \Nette\InvalidArgumentException("Micropayment without id passed");
		$id = $mp->id_micropayment;
		$mp->offsetUnset('id_micropayment');
		try {
			$this->connection->update('MicroPayments', $mp->toArray())->where('id_micropayment = %i', $id)
				->execute();
		} catch (DibiException $ex) {
			throw new InvalidArgumentException($ex->message);
		}
	}
	
	public function removeMicroPayment(MicroPayment $mp) {
		$id = $mp->id_micropayment;
		return $this->connection->delete('MicroPayments')->where('id_micropayment = %i', $id)
				->execute();
	}
}

