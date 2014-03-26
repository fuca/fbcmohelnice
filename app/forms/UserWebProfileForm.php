<?php
namespace florbalMohelnice\Forms;
use \Nette\DateTime,
	\Nette\Application\UI\Form,
	florbalMohelnice\Entities\WebProfile;

/**
 * Description of UserWebProfileForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class UserWebProfileForm extends Form {
	
	/** @var form mode*/ // ENUM create/update
	//private $mode;
	
	//public function getMode() {
	//	return $this->mode;
	//}
	
	//public function setMode($m) {
//		if ($m !='create' && $m != 'update') {
//			throw new \InvalidArgumentException("Mode has to be set on 'create' or 'update', '$m' given.");
//		}
//		$this->mode = $m;
//	}
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL/*, $mode = 'create'*/) {
		parent::__construct($parent, $name);
		
		//->setMode($mode);

		$this->addHidden('kid');
		$this->addText('city', 'Město');
		$this->addText('job', 'Zaměstnání');
		$this->addTextArea('contact', 'Kontakt', 30, 5);
		$this->addTextArea('personal_dislikes', 'Vadí', 30, 5);
		$this->addTextArea('personal_likes', 'Nevadí', 30, 5);
		$this->addTextArea('personal_interests', 'Zajímá mě', 30, 5);
		$this->addText('floorball_number', 'Moje čísla');
		$this->addTextArea('floorball_equipment', 'Výbava', 30, 5);
		$this->addText('floorball_brand', 'Značka');
		$this->addText('floorball_club', 'Klub');
		$this->addTextArea('floorball_experience', 'Nejsilnější zážitek', 30, 5);
		$this->addTextArea('floorball_to_fbcm', 'Jak jsem se dostal do FBC Mohelnice', 30, 5);
		$this->addTextArea('floorball_beginning', 'Florbalové začátky', 30, 5);
		$this->addTextArea('additional_information', 'Něco víc', 30, 5);
		
		$this->addSubmit('submitButton','Uložit');
		$this->onSuccess[] = callback($this, 'webProfileFormSubmitted');
	}
	
	public function webProfileFormSubmitted(Form $f) {
		$values = $f->getValues();
		$values['last_updated'] = new \Nette\DateTime();
		//switch ($this->getMode()) {
		//	case 'create':
		//		$this->presenter->createWebProfile(new WebProfile($values));
		//		break;
		//	case 'update':
				$this->presenter->updateWebProfile(new WebProfile($values));
		//		break;
		//}
	}	
}