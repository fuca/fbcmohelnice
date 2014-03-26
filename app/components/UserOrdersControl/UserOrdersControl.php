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
class UserOrdersControl extends \Nette\Application\UI\Control {

    private $kid;
    private $model;
    private $templateFile;

    public function setKid($k) {
	if (!is_numeric($k))
	    throw new Nette\InvalidArgumentException("Argument has to be type of numeric");
	$this->kid = $k;
    }

    public function setModel(florbalMohelnice\Models\OrdersModel $model) {
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
	$this->setTemplateFile(__DIR__ . "/default.latte");
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

	$this->template->render();
    }

    public function createComponentOrdersGrid($name) {
	$filterRenderType = Filter::RENDER_INNER;

	$grid = new Grid($this, $name);
	$grid->setModel($this->getModel()->getFluent($this->getKid()));
	
	$grid->setDefaultPerPage(30);
	$grid->setPrimaryKey('id');
	$grid->addColumn('label', 'Typ')
		->setSortable();

	$grid->addColumn('ordered_time', 'Zadáno', Column::TYPE_DATE)
		->setSortable();

	$grid->addColumn('handler', 'Vyřizuje')
		->setSortable();

	$grid->addColumn('state', 'Stav')
		->setSortable();

	$grid->addColumn('last_edit', 'Poslední změna', Column::TYPE_DATE)
		->setSortable();

	$grid->addColumn('comment', 'Komentář')
		->setTruncate(10)
		->setSortable();

	$grid->addAction('showOrder', 'Detail');

	$grid->setFilterRenderType($filterRenderType);
	$grid->setExporting();
    }

}
