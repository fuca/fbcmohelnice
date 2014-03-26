<?php
namespace florbalMohelnice\Components;

use Nette\Diagnostics\Logger,
	Grido\Grid,
	Grido\Components\Actions\Action,
    Grido\Components\Filters\Filter;
/**
 * Description of CreditsControl
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class CreditsControl extends \Nette\Application\UI\Control {
	
	/** @var render template file */
	private $templateFile;
	
	/** @var renderSubtotal template file */
	private $subtotalTemplateFile;
	
	/** @var kid */
	private $id;
	
	/** @var functional mode of component */
	private $editMode;
	
	/** @var data to display */
	private $data;
	
	public function getData() {
		if (!isset($this->data)) $this->data = $this->parent->getService('creditModel')->getDetailFluent($this->getId());
		return $this->data;
	}
	
	public function setEditMode($mode) {
		if (!is_bool($mode)) throw new \Nette\InvalidArgumentException('Argument has to be bool value');
    	$this->editMode = $mode;
	}
	
	public function getEditMode() {
		if (!isset($this->editMode)) $this->editMode = FALSE;
		return $this->editMode;
	}
	
	public function setId($kid) {
		if (!is_numeric($kid)) throw new \Nette\InvalidArgumentException("Argument has to be numeric");
		$this->id = $kid;
	}
	
	public function getId() {
		if (!isset($this->id)) throw new \Nette\InvalidStateException("Data is not set, nothing to be displayed");
		return $this->id;
	}
	
	public function __construct($parent, $name) {
		parent::__construct($parent, $name);
	}
	
	public function setTemplateFile($file) {
		if (!file_exists($file)) 
			throw new \Nette\InvalidArgumentException("Template file \"$file\" does not exists!");
		$this->templateFile = $file;
	}
	
	public function setSubtotalTemplateFile($fileName) {
		if (!file_exists($fileName)) throw new \Nette\InvalidArgumentException("Subtotal template file \"$fileName\" does not exists!");
		$this->subtotalTemplateFile = $fileName;
	}
	
	public function getSubtotalTemplateFile() {
		if (!isset($this->subtotalTemplateFile)) $this->subtotalTemplateFile = __DIR__.'/subtotal.latte';
		return $this->subtotalTemplateFile;
	}
	
	public function getTemplateFile() {
		if (!isset($this->templateFile)) $this->templateFile = __DIR__.'/default-template.latte';
		return $this->templateFile;
	}
	
	public function render() {
		$this->template->setFile($this->getTemplateFile());
		$this->template->render();
	}
	
	public function renderSubtotal() {
		$this->template->setFile($this->getSubtotalTemplateFile());
		$seasonId = $this->parent->getActualSeasonId();
		$subtotal = $supposed = 0;
		$data = $this->getData();
		try {
		    $supposed = $this->presenter->getGroupsModel()->getUsersHomeGroupCreditTax(new \florbalMohelnice\Entities\User(array('kid'=>$this->getId())), $seasonId);
		} catch(\Nette\IOException $ex) {
		    \Nette\Diagnostics\Debugger::log($ex->getMessage(), \Nette\Diagnostics\Debugger::ERROR);
		    $this->presenter->flashMessage("Nepodařilo se získat záznam o požadovaných kreditech pro sezónu $seasonId");
		}
		foreach ($data as $d) {
			$subtotal += $d->credit_count;
		}
		$this->template->sum = $subtotal;
		$this->template->supposed = $supposed;
		$this->template->remains = $supposed - $subtotal;
		$this->template->render();
	}
	
	public function createComponentUserCreditsGrid($name) {
		$filterRenderType = Filter::RENDER_INNER;
		
		$grid = new Grid($this, $name);
		$grid->setModel($this->getData());
		
		$grid->setDefaultPerPage(50);
		$grid->setPrimaryKey('id_credit');
		
		$grid->addColumn('subject', 'Předmět')
			->setSortable()
			->setFilter()
			->setSuggestion();

		$grid->addColumn('credit_count', 'Počet kreditů')
			->setSortable()
            ->setFilter()
            ->setSuggestion();
		
		$grid->addColumn('season', 'Sezóna')
			->setSortable()
            ->setFilter()
            ->setSuggestion();
		
		$grid->addColumn('ordered_time', 'Zadáno')
			->setSortable()
			->setFilter();
		
		if ($this->editMode) {
			$grid->addAction('edit', 'Upravit', Action::TYPE_HREF , 'editCredit');
			$grid->setOperations(array('delete'=>'Smazat'), callback($this, 'paymentsGridOperationsHandler'));

			$grid->setFilterRenderType($filterRenderType);
			$grid->setExporting();
		}	
	}
	
	/** in use */
	public function paymentsGridOperationsHandler($operation, $id) {
		switch ($operation) {
			case 'delete':
				foreach ($id as $i) {
					$this->deleteCredit($i);
				}
				break;
		}
	}
	
	public function deleteCredit($id) {
		try {
			$this->getService('creditModel')->removeCredit((integer)$id);
		} catch (\Nette\OutOfRangeException $ex) {
			\Nette\Diagnostics\Debugger::Log($ex->getMessage(), \Nette\Diagnostics\Debugger::ERROR);
			$this->flashMessage('Kreditový záznam neexistuje', 'error');
		}
		$this->flashMessage('Kreditový záznam smazán');
		$this->redirect('credits');
	}
}

