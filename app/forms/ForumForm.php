<?php
namespace florbalMohelnice\Forms;
use \Nette\DateTime,
	\Nette\Application\UI\Form,
	Nette\Forms\Controls\SubmitButton,
	Vodacek\Forms\Controls\DateInput,
	florbalMohelnice\Entities\Forum;


/**
 * Description of ForumForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class ForumForm extends Form {
	
	const CREATE_MODE = 'create';
	const UPDATE_MODE = 'update';
	
	const ALL_USERS = 'ALL';
	
	/** @var form mode*/ // ENUM create/update
	private $mode;
	
	/** @var groups for select list */
	private $groups;

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
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL, array $groupsSelect, array $pars, array $rights, $mode = self::CREATE_MODE) {
		parent::__construct($parent, $name);
		
		$this->setMode($mode);
		$this->setGroupsSelect($groupsSelect);
		$parents = $pars;
		
		$this->addHidden('id_forum');
		$this->addText('title', 'Titulek', 50)
			->addRule(Form::FILLED, 'Titulek musí být zadán');
			
		$this->addTextArea('description', 'Popisek fóra', 50);
		$this->addSelect('view_permission','Minimální práva', $rights)
			->setDefaultValue(2);
		//$this->addSelect('parent_forum', 'Nadřazené fórum', $parents);
		$this->addMultiSelect('categories', 'Kategorie', $this->getGroupsSelect(), 8)
			->addRule(\Nette\Forms\Form::FILLED, 'Vyberte nejméně jednu kategorii');
		
		$this->addSubmit('submitButton','Uložit');
		
		$this->onSuccess[] = callback($this, 'forumFormSubmitted');
	}
	
	/**
	 *
	 */
	public function forumFormSubmitted(Form $form) {
		$values = $form->getValues();
		$forum = new Forum($values);
		$now = new DateTime();
		$forum->offsetSet('update_time', $now);
		$forum->offsetSet('parent_forum', 1);
		switch($form->getMode()) {
			case self::CREATE_MODE:
				$this->presenter->addForum($forum);
				break;
			case self::UPDATE_MODE:
				$this->presenter->updateForum($forum);
				break;
		}
	}
}
		
