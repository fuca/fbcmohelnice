<?php

namespace florbalMohelnice\Forms;

use Nette\Application\UI\Form,
    Nette\DateTime,
    Nette\Forms\Container,
    Vodacek\Forms\Controls\DateInput,
    florbalMohelnice\Entities\Article;

/**
 * Description of ArticleForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class ArticleForm extends Form {

    const UPDATE_MODE = 'update';
    const CREATE_MODE = 'create';

    /** @var users list */
    private $users;

    /** @var form mode */ // ENUM create/update
    private $mode;

    /** @var groups for select list */
    private $groups;

    /** @var images in articles idir */
    private $images;

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
	    throw new \InvalidArgumentException(
	    "Mode has to be set on " . self::CREATE_MODE . " or " . self::UPDATE_MODE . ", '$m' given.");
	}
	$this->mode = $m;
    }

    public function setUsers(array $uss) {
	$this->users = $uss;
    }

    public function getUsers() {
	if (!isset($this->users) || !is_array($this->users)) {
	    throw new \Nette\InvalidStateException('Attribute users is not set');
	}
	return $this->users;
    }

    public function getImages() {
	return $this->images;
    }

    public function setImages(array $imgs) {
	$this->images = $imgs;
    }

    public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name, array $selUsers, array $grps, array $imgs, $mode = self::CREATE_MODE) {
	parent::__construct();

	$this->setMode($mode);
	$this->setUsers($selUsers);
	$this->setGroupsSelect($grps);
	$this->setImages($imgs);

	$rowLength = 65;
	$commentsMode = Article::getSelectCommModes();
	$status = Article::getStatusModes();

	$this->addHidden('id_article');

	$this->addText('title', '', $rowLength, '100')
		->addRule(Form::FILLED, 'Článek musí mít nějaký titulek.');

	//$this->addCheckbox('highlight', 'Zvýraznit na hl. straně'); // odstraneno dle pozadavku

	$this->addSelect('comments_mode', 'Komentáře', $commentsMode)
		->setDefaultValue(1);

	$form = $this;
	$cats = $this->addDynamic('categories', function (Container $container) use ($form) {

		    $container->addSelect('category', '', $container->form->getGroupsSelect())
			    ->addRule(Form::FILLED, 'Kategorie musí být vybrána')
			    ->setPrompt('');

		    $container->addSubmit('remove', 'Smazat')
				    ->setValidationScope(FALSE)
			    ->onClick[] = callback($form, 'dynamicCategoriesRemoveClicked');
		}, 0);

	$cats->addSubmit('add', 'Přidat kategorii')
			->setValidationScope(FALSE)
		->onClick[] = callback($this, 'dynamicCategoriesAddClicked');

	$this->addSelect('status', 'Stav', $status)
		->setDefaultValue(1);
	$this->addSelect('selectPicture', 'Vyber obrázek', $this->getImages())
		->setPrompt(' ');
	$this->addUpload('picture', "Nahraj obrázek");

	$this->addTextArea('content', NULL, $rowLength, '20')
		->getControlPrototype()->class('mceEditor')
		->addRule(Form::FILLED, 'Nelze přidat prázdný článek.');

	$this->addSubmit('submit', 'Uložit')
		->onClick[] = callback($this, 'sendArticleHandle');
	$this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
    }

    public function dynamicCategoriesRemoveClicked(\Nette\Forms\Controls\SubmitButton $button) {
	$row = $button->parent;
	$row->parent->remove($row);
    }

    public function dynamicCategoriesAddClicked(\Nette\Forms\Controls\SubmitButton $button) {

	if (!$button->form['categories']->isValid()) {
	    return;
	}
	$categories = $button->form['categories'];

	$nextName = $categories->countFilledWithout(array('add'));

	if (!isset($categories[$nextName])) {
	    $categories->createOne();
	}
    }
    
    public function setDefaults($values, $erase = FALSE) {
	$categories = $values->offsetGet('categories');
	$values->offsetUnset('categories');
	parent::setDefaults($values, $erase);
	
	if (!$this->isSubmitted()) {
	    foreach($categories as $cat) {
		$dynamic = $this['categories'];
		$one = $dynamic->createOne();
		$one['category']->setValue($cat);
	    }
	}
    }

    /**
     * 
     */
    public function sendArticleHandle(\Nette\Forms\Controls\SubmitButton $button) {
	$form = $button->form;
	if(!$form->isValid() || !$form['categories']->isAllFilled(array('add')))
	    return;
	$values = $button->form->getValues();
	
	$catsArray = array();
	foreach($form['categories']->values as $value) {    
	    array_push($catsArray, $value->category);
	}
	$values->offsetSet('categories', $catsArray);
	
	$article = new Article($values);
	$article->offsetSet('updated_time', new DateTime());
	
	switch ($this->getMode()) {
	    case self::CREATE_MODE:
		$this->presenter->createArticle($article);
		break;
	    case self::UPDATE_MODE:
		if ($article->picture == '')
		    $article->offsetUnset('picture');
		$article->offsetSet('id_article', $values['id_article']);
		$this->presenter->updateArticle($article);
		break;
	}
    }

}

