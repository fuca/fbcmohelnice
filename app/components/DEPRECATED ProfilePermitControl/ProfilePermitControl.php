<?php
namespace florbalMohelnice\Components;
use Nette\Application\UI\Control,
	florbalMohelnice\Models\UserModel,
	Grido\Grid,
	Grido\Components\Actions\Action,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
	Grido\Components\Columns\Date;

/**
 * Description of ProfilePermitControl
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class ProfilePermitControl extends Control {
	
	/** @var data to display */
	private $data;
	
	/** @var user model */
	private $userModel;
	
	/** @var template file */
	private $templateFile;
	
	public function getUserModel() {
		if (!isset($this->userModel)) throw new \Nette\InvalidStateException('User model has to be set');
		return $this->userModel;
	}
	
	public function setUserModel(UserModel $um) {
		$this->userModel = $um;
	}
	
	public function getTemplateFile() {
		if (!isset($this->templateFile)) $this->templateFile = __DIR__.'/default.latte';
		return $this->templateFile;
	}
	
	public function setTemplateFile($template) {
		if (!file_exists($template)) throw new \Nette\InvalidArgumentException('Template file doesn\'t exist');
		$this->templateFile = $template;
	}
	
	public function render() {
		$this->template->setFile($this->getTemplateFile());
		$this->template->render();
	}
	
	public function createComponentPermitGrid($name) {
		$filterRenderType = Filter::RENDER_INNER;
		
		$grid = new Grid($this, $name);
		$grid->setModel($this->getUserModel()->webProfilesForCheckFluent());
		
		$grid->setDefaultPerPage(30);
		$grid->setPrimaryKey('kid');
		
		$grid->addColumn('kid', 'KID')
			->setSortable()
			->setFilter()
				->setSuggestion();
		
		$grid->addColumn('surname', 'Příjmení')
			->setSortable()
			->setFilter()
				->setSuggestion();
		
		$grid->addColumn('name', 'Jméno')
			->setSortable()
			->setFilter()
				->setSuggestion();	
		
		$grid->addAction('show', 'Zobrazit', Action::TYPE_HREF , 'editWebProfile');
		$grid->setOperations(array('permit'=>'Schválit'), callback($this, 'editWebProfileOperationsHandler'));
		
		$grid->setFilterRenderType($filterRenderType);
		$grid->setExporting();
	}
	
	public function editWebProfileOperationsHandler($operation, $id) {
		switch ($operation) {
			case 'permit':
				foreach ($id as $i)
					$this->presenter->permitProfile($i);
				break;
		}
	}
}

