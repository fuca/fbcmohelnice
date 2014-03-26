<?php
namespace florbalMohelnice\Forms;
use	\Nette\Application\UI\Form,
	Nette\Forms\Controls\SubmitButton,
	florbalMohelnice\Entities\Participation;

/**
 * Description of ParticipationForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class ParticipationForm extends Form {
	
	const CREATE_MODE = 'create';
	const UPDATE_MODE = 'update';
	
	
	/** @var form mode */ // ENUM create/update
	private $mode;
	
	/** @var participation mode */ // ENUM TRUE/FALSE
	private $part;
	
	/** @var event id */
	private $id_event;
	
	/** @var kid */
	private $kid;
	
	public function getId_event() {
	    return $this->id_event;
	}

	public function setId_event($id_event) {
	    $this->id_event = $id_event;
	}

	public function getKid() {
	    return $this->kid;
	}

	public function setKid($kid) {
	    $this->kid = $kid;
	}

	public function getPart() {
	    return $this->part;
	}

	public function setPart($part) {
	    $this->part = $part;
	}

	public function getMode() {
		return $this->mode;
	}
	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE))
			throw new \InvalidArgumentException("Mode has to be set on ". self::CREATE_MODE . " or ". self::UPDATE_MODE.", '$m' given.");
		$this->mode = $m;
	}
	
	/**
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, $part, $kid, $id_event, $mode = self::CREATE_MODE) {
		parent::__construct($parent, $name);
		
		$this->setMode($mode);
		$this->setPart($part);
		$this->setKid($kid);
		$this->setId_event($id_event);
		
		$this->addHidden('id_event')
			->setDefaultValue($this->getId_event());
		$this->addHidden('kid')
			->setDefaultValue($this->getKid());
		$this->addHidden('participation')
			->setDefaultValue($this->getPart());
			 
 		$this->addTextArea('comment', 'Komentář', 40, 2);

		$this->addSubmit('submit', 'Odeslat')
			->onClick[] = callback($this, 'partFormSubmitted');
	}
	
	/**
	 */	
	public function partFormSubmitted (SubmitButton $button) {
		$form = $button->parent;		
		$values = $form->getValues();
		
		$part = new Participation($values);
		
		switch($this->getMode()) {
			case self::CREATE_MODE:			
				$this->parent->addParticipation($part);
				break;
			case self::UPDATE_MODE:
				$part->offsetSet('id_event', $values['id_event']);
				$this->parent->editParticipation($part);
				break;
		}
	}		
}
		
		
