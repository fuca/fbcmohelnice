<?php
namespace florbalMohelnice\Forms;
use \Nette\DateTime,
	\Nette\Application\UI\Form,
	Vodacek\Forms\Controls\DateInput,
	florbalMohelnice\Entities\WallPost;

/**
 * Description of WallPostForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class WallPostForm extends Form {
	
	const CREATE_MODE = 'create';
	const UPDATE_MODE = 'update';
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var groups for select list */
	private $groups;
	
	public function getGroupsSelect() {
		if (!isset($this->groups))
			throw new InvalidStateException('Groups attribute has to be set');
		return $this->groups;
	}
	
	public function setGroupsSelect(array $grps) {
		$this->groups = $grps;
	}
	
	public function getMode() {
		return $this->mode;
	}
	
	public function setMode($m) {
		if (($m != self::CREATE_MODE) && ($m != self::UPDATE_MODE)) {
			throw new \InvalidArgumentException("Mode has to be set on 'create' or 'update', '$m' given.");
		}
		$this->mode = $m;
	}
	
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $groupsSelect, $mode = 'create') {
		parent::__construct($parent, $name);
		
		$status = array(WallPost::STATUS_CONCEPT => 'Koncept', 
						WallPost::STATUS_PUBLISHED => 'Publikováno');
		$comMode = WallPost::getSelectCommModes();
		
		$this->setGroupsSelect($groupsSelect);
		$this->setMode($mode);
		
		$this->addHidden('id_wallpost');
		$this->addHidden('posted_kid');
		$this->addText('title', 'Titulek', 50)
			->addRule(Form::FILLED, 'Titulek není zadán');
			
		$this->addDate('show_from','Zobrazit od',  DateInput::TYPE_DATETIME_LOCAL)
			->setDefaultValue(new DateTime());
	
		$this->addDate('show_to','Zobrazit do',  DateInput::TYPE_DATETIME_LOCAL)
					->setDefaultValue(new DateTime('+7 days'));
			
		$this->addSelect('status', 'Stav', $status);
		$this->addSelect('comment_mode', 'Komentáře', $comMode);
			
		$this->addMultiSelect('categories', 'Kategorie', $this->getGroupsSelect(), 10);
			
		$this->addTextArea('content', 'Obsah', 50, 15)
			->getControlPrototype()->class('mceEditor')
			->addRule(Form::FILLED, 'Obsah není zadán');
			
		$this->addSubmit('submitButton','Uložit');
		$this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
		$this->onSuccess[] = callback($this, 'wallPostFormSubmitted');
	}
	
	public function wallPostFormSubmitted (Form $form) {
		$values = $form->getValues();
		$wallPost = new WallPost($values);
		$wallPost->offsetSet('updated_time', new DateTime());
		switch($this->getMode()) {
			case self::CREATE_MODE:			
				$this->presenter->addWallPost($wallPost);
				break;
			case self::UPDATE_MODE:
				$wallPost->offsetSet('id_wallpost', $values['id_wallpost']);
				$this->presenter->editWallPost($wallPost);
				break;
		}
	}
			
}
		
		
