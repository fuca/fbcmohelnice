<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\Order;

/**
 * Description of ordersModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class OrdersModel extends BaseModel {

    private function _getInnerSelectForFluent() {
	$innerSelect = $this->connection
		->select('Orders.id, kid, OrderTypes.label,ordered_time, state, last_edit, Orders.comment, order_type_id, specification, CONCAT(name,\' \', surname) AS author')
		->from('Orders')
		->join('Users')->using('(kid)')
		->join('OrderTypes')
		->on('OrderTypes.id = Orders.order_type_id');
	return $innerSelect;
    }

    private function _foldInnerByOuterFluent(\DibiFluent $inner) {
	$res = $this->connection->select('id, kid, label, author,ordered_time, state, last_edit, comment,CONCAT(name, \'  \', surname) AS handler, order_type_id, specification, handler_kid')
			->from($inner)->as('element')
			->leftJoin($this->connection->select('order_type_id, handler_kid, name, surname')->from('relUserOrderType')->join('Users')->on('handler_kid = kid'))->as('hm')->using('(order_type_id)');
	return $res;
    }

    public function getFluent($kid = NULL) {
	if ($kid != NULL && !is_numeric($kid))
	    throw new \Nette\InvalidArgumentException('Argument has to be type of numeric');

	$innerSelect = $this->_getInnerSelectForFluent();

	if ($kid != NULL)
	    $innerSelect = $innerSelect->where('kid = %i', $kid);
	$res = $this->_foldInnerByOuterFluent($innerSelect);
	return $res;
    }

    /// ADMIN FLUENT --- ADD CONDITION FOR SELECT ORDERS WITH ORDERED TIME <= NOW - 300 SEC 
    public function getAdminFluent($kid = NULL, $admin = FALSE) {
	$res = $this->getFluent();
	//if (!$admin)
	  //  $res = $res->where('ordered_time <= NOW() + 300 ');
	return $res;
    }

    public function getSelectTypes() {
	$result = $this->connection->select('[id],[label]')->from('OrderTypes')->execute()->fetchPairs();
	return $result;
    }

    public function createOrder(Order $order) {
	$i = $order;
	$i->offsetUnset('id');
	//$i->offsetUnset('author_kid');
	try {
	    $this->connection->insert('Orders', $i->toArray())->execute();
	} catch (\DibiException $ex) {
	    throw new \Nette\IOException($ex);
	}
    }

    public function getOrder($id) {
	if ($id != NULL && !is_numeric($id))
	    throw new \Nette\InvalidArgumentException('Argument has to be type of numeric');
	try {
	    $res = $this->_foldInnerByOuterFluent($this->_getInnerSelectForFluent())
		    ->where('id = %i', $id)
		    ->execute()
		    ->setRowClass('florbalMohelnice\Entities\Order')
		    ->fetch();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex);
	}
	return $res;
    }

    public function updateOrder(Order $o) {

	$orderId = $o->offsetGet('id');
	$o->offsetUnset('id');

//		$authorKid = $o->offsetGet('kid');
//		$o->offsetUnset('kid');
	if ($o->offsetExists('handler_kid')){
	    $handlerKid = $o->offsetGet('handler_kid');
	    $o->offsetUnset('handler_kid');
	}
	
	if ($o->offsetExists('state'))
	    $state = $o->offsetGet('state');

	/* 		switch ($state) {
	  case Order::REQUEST_STATE:
	  // smazat handlera
	  //$this->connection->delete('rel')
	  break;
	  case Order::INPROGRESS_STATE:

	  break;
	  case Order::SOLVED_STATE:
	  // nic
	  break;
	  case Order::CANCELED_STATE:
	  // nic
	  break;
	  default : throw new \Nette\InvalidArgumentException('Passed Order instance has invalid state attribute');
	  }
	 */

	try {
	    $this->connection->update('Orders', $o)->where('id = %i', $orderId)->execute();
	} catch (\DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	    return;
	}
    }

    public function getHandlersSelect() {
	try {
	    $res = $this->connection->select('kid,CONCAT(name,\' \',surname,\' \',kid)')
			    ->from('relUserOrderType')->join('Users')->on('handler_kid = kid')
			    ->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException('Error while fetchning data' . ' ' . $ex->getMessage());
	}
	return $res;
    }

    public function deleteOrder($id) {
	try {
	    $this->connection->delete('Orders')->where('id = %i', $id)->execute();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException('Error while deleting message with id ' . $id . ' -- ' . $ex->getMessage());
	}
    }

}

