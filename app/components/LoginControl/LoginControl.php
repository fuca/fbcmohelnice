<?php

namespace florbalMohelnice\Components;

/**
 * Description of LoginControl
 * 
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * Login control has two render methods one for link to log-in/log-out
 * and second one to render after-login menus. :-X
 * @package florbalMohelnice
 */
final class LoginControl extends \Nette\Application\UI\Control {

    /** log in link label */
    private $logInLabel;

    /** log out link label */
    private $logOutLabel;
    
    /** log in link text */
    private $logInText;

    /** log out link text */
    private $logOutText;
    
    /** log in link target */
    private $logInTarget;

    /** @var log out link target */
    private $logOutTarget;

    /** @var render template file */
    private $templateFile;

    /** @var status template file */
    private $templateStatus;
    
    private $privateMessagesCallback;
    
    public function setPrivateMessagesCallback(\Nette\Callback $privateMessagesCallback) {
	$this->privateMessagesCallback = $privateMessagesCallback;
    }
    
    public function __construct() {
	$this->logInLabel = 'Sign in';
	$this->logOutLabel = 'Sign out';
    }

    public function setInText($logInText) {
	$this->logInText = $logInText;
    }

    public function setOutText($logOutText) {
	$this->logOutText = $logOutText;
    }

    public function setTemplateFile($file) {
	if (!file_exists($file))
	    throw new \Nette\InvalidArgumentException("Template file \"$file\" does not exists!");
	$this->templateFile = $file;
    }

//    public function setTemplateStatus($file) {
//	if (!file_exists($file))
//	    throw new \Nette\InvalidArgumentException("Template file \"$file\" does not exists!");
//	$this->templateStatus = $file;
//    }

    public function setInTarget($link) {
	if ($link == FALSE)
	    throw new \Nette\InvalidArgumentException('Out target has to be non-epmty string!');
	$this->logInTarget = $link;
    }

    public function setOutTarget($link) {
	if ($link == FALSE)
	    throw new \Nette\InvalidArgumentException('Out target has to be non-epmty string!');
	$this->logOutTarget = $link;
    }

    public function setInLabel($label) {
	if ($label == FALSE)
	    throw new \Nette\InvalidArgumentException('Argument has to be non-empty string!');
	$this->logInLabel = $label;
    }

    public function setOutLabel($label) {
	if ($label == FALSE)
	    throw new \Nette\InvalidArgumentException('Argument has to be non-empty string!');
	$this->logOutLabel = $label;
    }

    public function renderForm() {
	$this->template->setFile($this->templateForm); //__DIR__ . '/form.latte
	$this->template->render();
    }

    public function render() {

	$this->template->setFile($this->templateFile);
	$user = $this->presenter->user;
	$identity = NULL;
	$isLoggedIn = $user->isLoggedIn();
	$link_label = NULL;
	$link_target = NULL;
	$name = NULL;
	$surname = NULL;
	$id = NULL;
	$pms = NULL;

	if ($isLoggedIn) {
	    $identity = $user->identity;
	    $name = $identity->data["name"];
	    $surname = $identity->data["surname"];
	    $id = $identity->id;
	    $link_target = $this->logOutTarget;
	    $link_label = $this->logOutLabel;
	    $link_text = $this->logOutText;
	    $pms = $this->privateMessagesCallback->invoke($id);
	} else {
	    $link_label = $this->logInLabel;
	    $link_target = $this->logInTarget;
	    $link_text = $this->logInText;
	}
	
	$adminMenu = FALSE;
	if ($this->presenter->user->isInRole('administrator') ||
		$this->presenter->user->isInRole('executive') ||
		$this->presenter->user->isInRole('responsible') ||
		$this->presenter->user->isInRole('secretary') ||
		$this->presenter->user->isInRole('assistant') ||
		$this->presenter->user->isInRole('editor'))
	    $adminMenu = TRUE;
	
	$messagesMenu = FALSE;
	if ($this->presenter->user->isAllowed('User:messageBox'))
	    $messagesMenu = TRUE;
	
	$this->template->messagesMenu = $messagesMenu;
	$this->template->adminMenuPredicate = $adminMenu;
	$this->template->name = $name;
	$this->template->surname = $surname;
	$this->template->id = $id;
	$this->template->loggedIn = $isLoggedIn;
	$this->template->link_label = $link_label;
	$this->template->link_target = $link_target;
	$this->template->link_text = $link_text;
	$this->template->pmsCount = $pms;
	
	$this->template->render();
    }

    public function createComponentLoginForm($name) {
	return new \Forms\LoginForm($this, $name);
    }

    public function createComponentUserMenu($name) {
	$res = $this->presenter->createComponentUserMenu($name);
	return $res;
    }

    public function createComponentAdminMenu($name) {
	$res = $this->presenter->createComponentAdminMenu($name);
	return $res;
    }

    public function createComponentClubMenu($name) {
	$res = $this->presenter->createComponentClubMenu($name);
	return $res;
    }

    public function createComponentMailMenu($name) {
	$res = $this->presenter->createComponentMailMenu($name);
	return $res;
    }
}
