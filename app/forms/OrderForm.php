<?php
namespace florbalMohelnice\Forms;
use Nette\Application\UI\Form,
	Nette\DateTime,
	Vodacek\Forms\Controls\DateInput,
	florbalMohelnice\Entities\Order;

/**
 * Description of OrdersForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class OrderForm extends  Form {
	
	const CREATE_MODE = 'create';
	const UPDATE_MODE = 'update';
	const ORDER_DELAY_SECONDS = 1800;
	
	const CONTEXT_USER = 'user';
	const CONTEXT_ADMIN = 'admin';
	
	/** @var orders type enumeration for select */
	private $ordersType;
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var select users array */
	private $users;
	
	/** @var array of old order values */
//	private $oldOrder; 
	
	/** @var id if mode edit */
	private $id;
	
	/** @var context */
	private $context;
	
	public function getContext() {
	    return $this->context;
	}

	public function setContext($context) {
	    if ($context != self::CONTEXT_ADMIN && $context != self::CONTEXT_USER)
		throw new \Nette\InvalidArgumentException("Order form context out of bounds");
	    $this->context = $context;
	}
	
//	public function setOldOrder(Order $o) {
//		$this->oldOrder = $o->toArray();
//	}
//	
//	public  function getOldOrder() {
//		return $this->oldOrder;
//	}

	public function getUsers() {
		if (!isset($this->users)) throw new \Nette\InvalidStateException('Users array data for select input has to be set');
		return $this->users;
	}
	
	public function setUsers(array $usrs) {
		$this->users = $usrs;
	}
	
	public function getOrdersType() {
		if (!isset($this->ordersType)) throw new \Nette\InvalidStateException('Orders type enumeration has to be set');
		return $this->ordersType;
	}
	
	public function setOrdersType(array $enum) {
		$this->ordersType = $enum;
	}
	
	public function getMode() {
		return $this->mode;
	}
	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE))
			throw new \InvalidArgumentException("Mode has to be set on 'create' or 'update', '$m' given.");
		$this->mode = $m;
	}
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, $ordersType, $selUsers, $context = self::CONTEXT_USER, $mode = self::CREATE_MODE) {
		parent::__construct($parent, $name);
		
		$submitLabel = 'Vytvořit objednávku';
		$this->setOrdersType($ordersType);
		$this->setMode($mode);
		$this->setUsers($selUsers);
		$this->setContext($context);
		if ($this->getContext() != self::CONTEXT_ADMIN)
		    $this->addHidden('kid');
		
		if ($this->getMode() == self::UPDATE_MODE) 
			$this->addText ('id', 'ID', 5)
				->setDisabled ();

		$type = $this->addSelect('order_type_id','Typ', $this->getOrdersType())
				->setPrompt('Není vybrán')
				->addRule(Form::FILLED, 'Druh objednávky není vybrán');
		
//		if ($this->getMode() == self::UPDATE_MODE) 
//			$type->setDisabled();
		
		if ($this->getContext() == self::CONTEXT_ADMIN)
			$this->addSelect('kid','Zájemce', $this->getUsers())
			->setDefaultValue($this->presenter->getUserId())
			    ->addRule(Form::FILLED, "Zájemce objednávky musí být zadán.");
		
		if ($this->getMode() == self::UPDATE_MODE) {
			$this->addDate('ordered_time', 'Objednáno', DateInput::TYPE_DATETIME_LOCAL);
			$this->addDate('last_edit','Posl. změna',  DateInput::TYPE_DATETIME_LOCAL)
				->setDisabled();
			$this->addSelect ('handler_kid', 'Vyřizuje', $this->getUsers())
				->setPrompt('Zatím nikdo')
				->setDisabled();
			$this->addSelect('state', 'Stav', \florbalMohelnice\Entities\Order::getStates());
			$submitLabel = 'Uložit změny';
		}
		
		$this->addTextArea('specification','Obsah', 40, 10)
				->addRule(Form::FILLED, 'Obsah objednávky není vyplněn');
			
		if ($this->getMode() == self::UPDATE_MODE || $this->getContext() == self::CONTEXT_ADMIN)
			$this->addTextArea ('comment','Komentář', 40, 5);
		
		$this->addSubmit('submit',$submitLabel);
		$this->onSuccess[] = callback($this, 'submitOrderForm');
	}
	
	/** Because el->setDisabled() makes el's value unreachable within submitOrderForm function */
	public function setDefaults ($values, $erase = FALSE) {
	    parent::setDefaults($values, $erase);
	    $this->id = $values['id'];
	}
	
	public function submitOrderForm (Form $form) {
		$values = $form->getValues();
		$now = new \Nette\DateTime();		
		// DODELAT OCHRANU PODLE PRAV, KDO UVIDI JAKY OBJEDNAVKY -- resp rozdelit to na ty, co si muzu vzit ke sprave a ty co vyrizuju
		switch ($form->getMode()) {
			case self::CREATE_MODE:
				$order = new \florbalMohelnice\Entities\Order($values);
				$nowPlusFive = new \Nette\DateTime('+ 300 seconds');
				$order->offsetSet('ordered_time', $nowPlusFive);
				$order->offsetSet('state', Order::REQUEST_STATE);
				$order->offsetSet('last_edit', $now);
				$this->presenter->addOrder($order);
				break;
			case self::UPDATE_MODE:
			    
		//$changedData = @array_diff_assoc((array) $values, $oldOrder);
				
				$order = new \florbalMohelnice\Entities\Order($values);
				$order->offsetSet('last_edit', $now);
				$order->offsetSet('handler_kid', $form->getComponent('handler_kid')->getValue());
				$order->offsetSet('id', $this->id);
				$order->offsetSet('kid', $values['kid']);
				$order->offsetSet('order_type_id', $values['order_type_id']);
				
				$this->presenter->editOrder($order);
				break;
		}

	}
	
//	public function setDefaults($values, $erase = FALSE) {
//	    parent::setDefaults($values, $erase);
//	    $this->setOldOrder($values);
//	}
}

