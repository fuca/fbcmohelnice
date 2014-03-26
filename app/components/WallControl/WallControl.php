<?php
namespace florbalMohelnice\Components;
use \Nette\DateTime;
/**
 * Description of WallControl
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class WallControl extends \Nette\Application\UI\Control {

	/** @var general templatefile */
	private $templateFile;
	
	/** @var messages fluent */
	private $fluent;
	
	public function setFluent(\DibiFluent $fluent) {
		$this->fluent = $fluent;
	}
	
	public function getFluent() {
		if (!isset($this->fluent)) 
			throw new \Nette\InvalidStateException('Attribute fluent is not set');
		return $this->fluent;
	}

	public function getTemplateFile() {
		if (!isset($this->templateFile)) $this->templateFile = __DIR__."/general.latte";
		return $this->templateFile;
	}
	
	public function setTemplateFile($file) {
		if (!file_exists($file)) throw new \Nette\InvalidArgumentException("Passed file doesn't exist");
		$this->templateFile = $file;
	}

	public function __construct($parent, $name) {
		parent::__construct($parent, $name);
	}
	
	public function render() {
		$data = array();
		$actualData = array();
		$oldData = array();
		$now = new DateTime();

		$this->template->setFile($this->getTemplateFile());

		try {
			$data = $this->getFluent()->execute()->fetchAssoc('id_wallpost');
		} catch(DibiException $ex) {
			throw new IOException($ex->getMessage());
		}
		foreach ($data as $key=>$a) {
			if ($a->show_from <= $now && $a->show_to >= $now) {
				$actualData[$key] = $a;
				}
			else {
				$oldData[$key] = $a;				}
		}
		
		$this->template->actualData = $actualData;
		$this->template->oldData = $oldData;
		$this->template->render();
	}
	
}
