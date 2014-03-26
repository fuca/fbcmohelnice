<?php

namespace florbalMohelnice\Forms;

/**
 * Description of LogInForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice\Forms
 */
final class LogInForm extends \Nette\Application\UI\Form {
	
	private $backlink; 
	
	public function setBacklink($bl) {
	    $this->backlink = $bl;
	}
	
	public function __construct (\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
		
		parent::__construct($parent, $name);
		$this->backlink = NULL;
		
		$this->addText('username', 'Váš KID:')
				->setRequired('Please enter your club indentification number');

		$this->addPassword('password', 'Heslo:')
			->setRequired('Please enter your password.');

		$this->addCheckbox('remember', 'Keep me signed in');

		$this->addSubmit('send', 'Log me in!');

		$this->onSuccess[] = callback($this, 'signInFormSucceeded');
	}


	public function signInFormSucceeded(LogInForm $form) {
		
		$presenter = $this->getPresenter();
		$values = $form->getValues();

		if ($values->remember) {
			$presenter->getUser()->setExpiration('+ 14 days', FALSE);
		} else {
			$presenter->getUser()->setExpiration('+ 40 minutes', TRUE);
		}

		try {
			$presenter->getUser()->login((integer)$values->username, $values->password);
		} catch (\Nette\Security\AuthenticationException $e) {
			$ip = $this->presenter->context->httpRequest->getRemoteAddress();
			\Nette\Diagnostics\Debugger::log("Unsuccessful login attempt from remote:" . $ip . " " . $e->getMessage());
			$form->addError($e->getMessage());
			return;
		}
		$this->presenter->restoreRequest($this->backlink);
		$presenter->redirect('User:');
	}
}