<?php

/**
 * Desc. CategoryPresenter
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package fbcmoh
 */
final class CategoryPresenter extends BasePresenter {
    
	/** @var category indentifier */
	private $category;
	
	/** @var groups identifiers */
	private $groups;
	
	public function startup(){
		parent::startup();
	}
	
	public function beforeRender(){
		$param = $this->getParam();
		//dump($param);
		
		if (!is_string($param['team']) || $param['team'] == '')
			throw new InvalidArgumentException('Argument has to be non-empty string.'); 
 		$this->category = $param['team'];
		// natahat z db asociovane kategorie dle klice s popisem pro vypisy
		$this->template->layoutStyle = $this->getLayoutStyle();
	}
	
	public function actionDefault($team) {
		
	}
	public function renderDefault() {
		$this->template->category = $this->category;
	}
	
	public function actionInfo($team) {
		
	}
	public function renderInfo() {
		$this->template->category = $this->category;
	}
	
	// nejspis bude vhodnejsi udelat filtr ve clancich a tady ho na nej pouze smerovat
	public function actionArticle($team) {
	    
	}
	
	public function renderArticle() {
		$this->template->category = $this->category;
	}
	
	public function actionLeague($team) {
		
	}
	public function renderLeague() {
		$this->template->category = $this->category;
	}
	
	public function actionRoster($team) {
		
	}
	public function renderRoster() {
		$this->template->category = $this->category;
	}
	
	
}

