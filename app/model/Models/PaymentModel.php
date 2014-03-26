<?php
namespace florbalMohelnice\Models;
use \florbalMohelnice\Entities\Payment;
/**
 * Description of PaymentManager
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class PaymentModel extends BaseModel {
	
	public function __construct(\DibiConnection $conn) {
		parent::__construct($conn);
	}
	
	public function createPayment(Payment $py) {
		$py->offsetUnset('id_payment');
		$py->offsetSet('vs', $py->kid.$py->season.$py->amount);
		$this->connection->insert('Payments', $py->toArray())
				->execute();
		return $this->connection->insertId();
	}
	
	public function removePayment($id) {
		$b = $this->connection->delete('Payments')
				->where('id_payment = %i', $id)
				->execute();
		if ($b == 0) {
			throw new \Nette\OutOfRangeException("Platba s id $id neexistuje.");
		}
	}
	
	public function updatePayment(Payment $py) {
		$this->connection->update('Payments',$py->toArray())
				->where('id_payment = %i', $py->id_payment)
				->execute();
	}
	
	public function getPayment($id) {
		$result = $this->connection->select('*')->from('Payments')
				->where('id_payment = %i', $id)
				->execute()->setRowClass('florbalMohelnice\Entities\Payment')
				->fetch();
		if ($result === NULL) throw new \Nette\ArgumentOutOfRangeException("Platba s id $id nebyla nalezena.");
		return $result;
	}
	
	public function getFluent($id = NULL) {
	    $res = $this->connection->select('
			[id_payment],
			[surname],
			[name],
			[Payments.kid],
			[season],
			[subject],
			[amount],
			[vs],
			[comment],
			[pay_day],
			[due_date],
			[ordered_time],
			[ordered_kid],
			[status]')->from('Payments')->innerJoin('Users')->using('(kid)');
	    if ($id != NULL && is_numeric($id))
		$res->where("kid = %i", $id);
	    return $res;
	}
	
	public function getUsersFluent($kid) {
		if ($kid === NULL) throw new \Nette\InvalidArgumentException("Argument can't be null");
		return $this->connection->select('
			[id_payment],
			[Payments.kid],
			[season],
			[subject],
			[amount],
			[vs],
			[comment],
			[pay_day],
			[due_date],
			[ordered_time],
			[ordered_kid],
			[status]')->from('Payments')->innerJoin('Users')->using('(kid)')->where('kid = %i', $kid);
		
	}
}

