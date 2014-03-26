<?php

use \Nette\Application\UI\Form,
	 Nette\Diagnostics\Debugger,
	 florbalMohelnice\Entities\WebProfile;
/**
 * Desc. SecuredPresenter
 *
 * @author Michal Fučík michal.fuca.fucik@gmail.com
 * @package fbcmoh
 */
 abstract class SecuredPresenter extends BasePresenter {
	
	/** @var array of users for select input */
	private $selectUsers;
	
	public function getSelectUsers() {
		if (!isset($this->selectUsers)) $this->selectUsers = $this->getUserModel()->getSelectUsers();
		return $this->selectUsers;
	}
	
	/** @var logged in user kid*/
	private $userId;
	
	public function getUserId() {
		return (integer) $this->userId;
	}
	
	public function startup() {
		
		parent::startup();
		
		$user = $this->getUser();
		$this->userId = $user->getId();
		if (!$user->isLoggedIn()){
			if ($user->getLogoutReason() === \Nette\Security\User::INACTIVITY) {
                $this->flashMessage('Uplynula doba neaktivity! Systém vás z bezpečnostních důvodů odhlásil.', 'warning');
				$this->redirect('Auth:logIn');
            } else {
				$backlink = $this->getApplication()->storeRequest();
				$this->redirect('Auth:logIn', array('backlink' => $backlink));
			}
			
			// authorization part
			/*
			if (!$user->isAllowed($this->name, $this->action)) {
                $this->flashMessage('Na vstup do této sekce nemáte dostatečné oprávnění!', 'warning');
                $this->redirect('Homepage:default');
            }*/
		}
			
	}
	
	public function beforeRender(){
		$p = $this->user->identity->data['profile_required'];
		$pws = $this->user->identity->data['password_status'];
		
		$pwPrompt = "";
		if ($pws == WebProfile::USER_PASS_CHANGE_REQUIRED) {
		    $pwPrompt = WebProfile::USER_PASS_PROMPT;
		}
		
		$this->template->passwordResetPrompt = $pwPrompt;

		$prompt = "";
		switch ($p) {
			case WebProfile::USER_PROFILE_EDITED:
				$prompt = WebProfile::USER_PROMPT_EDITED;
				break;
			case WebProfile::USER_PROFILE_REQUIRED:
				$prompt = WebProfile::USER_PROMPT_REQUIRED;
				break;
		}
		$this->template->webProfilePrompt = $prompt;
	}
	public function actionDefault() {}
	public function renderDefault() {}
	
	
}

