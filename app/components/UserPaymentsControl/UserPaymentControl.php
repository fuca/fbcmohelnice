<?php
use Grido\Components\Filters\Filter,
    Grido\Grid,
    Grido\Components\Actions\Action,
    Grido\Components\Columns\Column,
    Grido\Components\Columns\Date;

/**
 * Description of UserPaymentControl
 *
 * @author fuca
 */
class UserPaymentsControl extends \Nette\Application\UI\Control {
    
    private $kid;
    
    private $model;
    
    private $templateFile;
    
    public function setKid($k) {
	if (!is_numeric($k))
	    throw new Nette\InvalidArgumentException("Argument has to be type of numeric");
	$this->kid = $k;
    }
    
    public function setModel(florbalMohelnice\Models\PaymentModel $model) {
	if ($model == NULL)
	    throw new Nette\InvalidArgumentException("Argument can't be NULL");
	$this->model = $model;
    }
    
    public function setTemplateFile($path) {
	if (!file_exists($path))
	    throw new Nette\FileNotFoundException("Given template file does not exist!");
	$this->templateFile = $path;
    }
    
    public function __construct($parent = NULL, $name = NULL) {
	parent::__construct($parent, $name);
	$this->setTemplateFile(__DIR__ ."/default.latte");
    }

    public function getKid() {
	return $this->kid;
    }

    public function getModel() {
	return $this->model;
    }

    public function getTemplateFile() {
	return $this->templateFile;
    }
    
    public function render() {
	$this->template->setFile($this->getTemplateFile());
	try {
	    $payments = $this->getModel()->getFluent($this->kid)->execute()->fetchAll();
	} catch (DibiException $ex) {
	    \Nette\Diagnostics\Debugger::log($ex->getMessage(), \Nette\Diagnostics\Debugger::ERROR);
	    $payments = FALSE;
	}
	$this->template->payments = $payments;
	$this->template->render();
    }
    
    public function createComponentPaymentsGrid($name) {
		$filterRenderType = Filter::RENDER_INNER;
		
		$grid = new Grid($this, $name);
		$grid->setModel($this->getModel()->getFluent($this->getKid()));
		
		$grid->setDefaultPerPage(30);
		$grid->setPrimaryKey('id_payment');
		
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
		
		$grid->addColumn('season', 'Sezóna')
			->setSortable()
			->setFilter()
				->setSuggestion();
		
		$grid->addColumn('subject', 'Předmět')
			->setSortable()
			->setFilter()
				->setSuggestion();
		
		$grid->addColumn('amount', 'Částka')
			->setSortable()
			->setFilter()
				->setSuggestion();
		
		$grid->addColumn('due_date', 'Splatnost', Column::TYPE_DATE)
			 ->setSortable()
			 ->setDateFormat(Date::FORMAT_DATE)
			 ->setFilter();
		
		$grid->addColumn('status', 'Zaplaceno')
			->setReplacement(array('unp'=>'Ne','pac'=>'Hotově','paa'=>'Účet'))
			->setSortable()
			->setFilter()
				->setSuggestion();
		
		$grid->addAction('edit', 'Upravit', Action::TYPE_HREF , 'editPayment');
		$grid->setOperations(array('delete'=>'Smazat'), callback($this, 'paymentsGridOperationsHandler'));
		
		$grid->setFilterRenderType($filterRenderType);
		$grid->setExporting();
    }
}
