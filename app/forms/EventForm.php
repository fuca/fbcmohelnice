<?php
namespace florbalMohelnice\Forms;
use \Nette\DateTime,
	\Nette\Application\UI\Form,
	Nette\Forms\Controls\SubmitButton,
	Vodacek\Forms\Controls\DateInput,
	florbalMohelnice\Entities\Event,
	Nette\Forms\Container;

/**
 * Description of EventForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class EventForm extends Form {
	
	const CREATE_MODE = 'create';
	const UPDATE_MODE = 'update';
	
	const ALL_USERS = 'ALL';
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var groups for select list */
	private $groups;
	
	/** @var Event types for select list */
	private $types;
	
	/** @var users list */
	private $users;

	
	/**
	 *
	 */	
	public function getEventTypes() {
		if (!isset($this->types))
			throw new InvalidStateException('Event types attribute has to be set');
		return $this->types;
	}
	
	/**
	 *
	 */	
	public function setEventTypes(array $ts) {
		$this->types = $ts;
	}
	
	/**
	 *
	 */	
	public function getGroupsSelect() {
		if (!isset($this->groups))
			throw new InvalidStateException('Groups attribute has to be set');
		return $this->groups;
	}
	
	/**
	 *
	 */	
	public function setGroupsSelect(array $grps) {
		$this->groups = $grps;
	}
	
	/**
	 *
	 */
	public function getMode() {
		return $this->mode;
	}
	
	/**
	 *
	 */	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE)) {
			throw new \InvalidArgumentException("Mode has to be set on 'create' or 'update', '$m' given.");
		}
		$this->mode = $m;
	}
	
	/**
	 *
	 */	
	public function setUsers(array $uss) {
		$this->users = $uss;
	}
	
	/**
	 *
	 */	
	public function getUsers() {
		if (!isset($this->users) || !is_array($this->users)) {
			throw new InvalidStateException('Attribute users is not set');
		}
		return array(self::ALL_USERS=>'* Celá kategorie *') + $this->users;
	}
	
	/**
	 *
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $groupsSelect, $types, $users, $mode = self::CREATE_MODE) {
		parent::__construct($parent, $name);
		
		$participation  = Event::getParticipationModes();
		$visibility 	= Event::getVisibilityModes();
		$comMode	= Event::getCommModes();
		
		$this->setGroupsSelect($groupsSelect);
		$this->setMode($mode);
		$this->setEventTypes($types);
		$this->setUsers($users);
		
		$this->addHidden('id_event');
		$this->addHidden('ordered_kid');
		
		$this->addSelect('event_type', 'Typ', $this->getEventTypes())
			 ->addRule(Form::FILLED, 'Typ musí být vybrán');
			 
		$this->addText('title', 'Titulek', 50)
			 ->addRule(Form::FILLED, 'Titulek není zadán');
			 
		$this->addDate('take_place_from', 'Od', DateInput::TYPE_DATETIME_LOCAL)
			 ->setDefaultValue(new DateTime());
			
		$this->addDate('take_place_to','Do',  DateInput::TYPE_DATETIME_LOCAL)
			 ->setDefaultValue(new DateTime('+2 hours'));
		
		$this->addDate('confirm_until','Vyjádřit do:',  DateInput::TYPE_DATETIME_LOCAL)
			 ->setDefaultValue(new DateTime('+2 hours'));
	
//		$this->addSelect('participation_mode','Účast', $participation)
//			 ->addRule(Form::FILLED, 'Typ účasti musí být zadán');
	
		$this->addMultiSelect('categories', 'Kategorie', $this->getGroupsSelect(), 8)
			 ->addRule(Form::FILLED, 'Kategorie musí být vybrána');
			
		$this->addSelect('visibility', 'Viditelnost', $visibility)
			 ->addRule(Form::FILLED, 'Viditelnost musí být zadána');
		
		$this->addSelect('comment_mode', 'Komentáře', $comMode);
		/*	 
		$users = $this->getUsers();
		$this->addDynamic('parties', function (Container $cont) use ($users) {
        	$cont->addSelect('user', '', $users);
					  
			$cont->addText('comment', '', 25);
				 
			$cont->addSubmit('remove', 'Smazat')
				 ->setValidationScope(FALSE)
				 ->onClick[] = callback($cont->form, 'delPartyHandle');
			}, 1);
			 
		
		$this->addSubmit('add', 'Přidat člena')
			 ->setValidationScope(FALSE)
			 ->onClick[] = callback($this, 'addPartyHandle');
			 */
			 
 		$this->addTextArea('description', 'Popis', 50, 7)
			 ->addRule(Form::FILLED, 'Popis musí být zadán');

		$xbutton = $this->addSubmit('submitButton', 'Uložit událost')
			->onClick[] = callback($this, 'eventFormSubmitted');
	}
	
//	/**
//	 *
//	 */	
//	public function delPartyHandle(SubmitButton $button) {
//    	$row = $button->parent;
//    	$form = $button->form;
//    	$parties = $form['parties'];
//    	if (sizeof($parties->values) > 1)
//	     	$row->parent->remove($row, FALSE); 
//     	return;
//	}
//	
//	/**
//	 *
//	 */
//	public function addPartyHandle(SubmitButton $button) {
//		$form = $button->form;
//		$parties = $form['parties'];
//		foreach($parties->values as $p) {
//			if ($p->user == self::ALL_USERS) {
//				$form->addError('Už není koho přidat');
//				return;
//			}
//		}
//		$id = $parties->countFilledWithout(array('remove'));
//		$parties->createOne();
//	}
	
	/**
	 *
	 */	
	public function eventFormSubmitted (SubmitButton $button) {
		$form = $button->parent;
		$userCats = $form['categories']->getValue();
		/*$userParties = $form['parties']->values;*/
		$values = $form->getValues();
		$event = new Event($values);
		$event->offsetSet('ordered_time', new DateTime());
		
		switch($this->getMode()) {
			case self::CREATE_MODE:			
				$this->presenter->addEvent($event);
				break;
			case self::UPDATE_MODE:
				$event->offsetSet('id_event', $values['id_event']);
				$event->offsetSet('ordered_kid', $values['ordered_kid']);
				$this->presenter->editEvent($event);
				break;
		}
	}
			
}
		
		
