<?php

namespace florbalMohelnice\Forms;

use Nette\Application\UI\Form,
    florbalMohelnice\Entities\PrivateMessage;

/**
 * Description of EventParticipationAdminForm
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class EventParticipationAdminForm extends \Nette\Application\UI\Form {

    private $users;

    public function getUsers() {
	return $this->users;
    }

    public function setUsers(array $usrs) {
	$this->users = $usrs;
    }

    public function __construct($parent, $name, $idEvent, array $selUsers) {
	parent::__construct();

	$this->setUsers($selUsers);

	$this->addHidden('id_event', $idEvent);
	$form = $this;
	$participations = $this->addDynamic('participations', function (\Nette\Forms\Container $container) use ($form) {

		    $container->addSelect('user', '', $container->form->getUsers())
			    ->addRule(Form::FILLED, 'Uživatel musí být vybrán')
			    ->setPrompt('');
		    $container->addText('comment', '');

		    $container->addSubmit('remove', 'Smazat')
				    ->setValidationScope(FALSE)
			    ->onClick[] = callback($form, 'dynamicUsersRemoveClicked');
		}, 0);

	$participations->addSubmit('add', 'Přidat uživatele')
			->setValidationScope(FALSE)
		->onClick[] = callback($this, 'dynamicUsersAddClicked');

	$this->addSubmit('send', 'Uložit docházku události')
		->onClick[] = callback($this, 'sendParticipationHandle');
    }

    public function sendParticipationHandle(\Nette\Forms\Controls\SubmitButton $button) {

	$form = $button->form;
	if (!$form->isValid() || !$form['participations']->isAllFilled(array('add')))
	    return;
	$values = $button->form->getValues();
	$idEvent = $values['id_event'];

	$array = array();
	foreach ($form['participations']->values as $value) {
	    $array[$value->user] = $value->comment;
	}
	$this->parent->addParticipationsByAdmin($idEvent, $array);
    }

    public function dynamicUsersRemoveClicked(\Nette\Forms\Controls\SubmitButton $button) {
	$row = $button->parent;
	$row->parent->remove($row);
    }

    public function dynamicUsersAddClicked(\Nette\Forms\Controls\SubmitButton $button) {

	if (!$button->form['participations']->isValid()) {
	    return;
	}
	$participations = $button->form['participations'];

	$nextName = $participations->countFilledWithout(array('add'));

	if (!isset($participations[$nextName])) {
	    $participations->createOne();
	}
    }

}