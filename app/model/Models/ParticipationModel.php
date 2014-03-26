<?php

namespace florbalMohelnice\Models;
use \florbalMohelnice\Entities\Participation;
/**
 * Description of ParticipationModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
final class ParticipationModel extends BaseModel {

    /**
     * Returns open fluent for all participations (necessary for datagrid)
     * @return DibiFluent
     */
    public function getFluent() {
	return $this->connection->select('*')->from('relParticipation');
    }
    
    public function getAll($id_event = NULL, $part = NULL, $groupBy = NULL) {
	if (!is_numeric($id_event))
	    throw new \Nette\InvalidArgumentException('Argument id_event has to be type of numeric');
	try {
	    $res = $this->connection->select('relParticipation.*, CONCAT(Users.surname, \' \', Users.name, \' (\', Users.kid, \')\') AS name')
			->from('relParticipation')
			->join('Users')->using('(kid)');
	    if ($id_event !== NULL)
		$res->where('id_event = %i', $id_event);
	    
	    if ($part !== NULL)
		$res->where('participation = %s', $part);
	    
	    if ($groupBy !== NULL)
		$res->groupBy($groupBy);
	    
	    $res = $res->execute()
		    ->setRowClass('florbalMohelnice\Entities\Participation')
		    ->fetchAssoc('kid');
	} catch(DibiException $x) {
	    throw new \Nette\IOException($x->getMessage(), $x);
	}
	return $res;
    }
    
    
    
    /**
     * Checks if passed Participation is able to database crud operations
     * @param \florbalMohelnice\Entities\Participation $part
     * @throws \Nette\InvalidArgumentException
     */
    private function participIntegrityTest(Participation $part) {
	if (!$part->offsetExists('kid') || !$part->offsetExists('id_event'))
	    throw new \Nette\InvalidArgumentException('Passed argument integrity is not valid. Kid or id_event attribute is not set.');
    }
    
    public function resetParticipation($kid, $id_event) {
	if (!is_numeric($kid) || !is_numeric($id_event))
	    throw new \Nette\InvalidArgumentException("One or both arguments are not type of numeric");
	try {
	    $this->connection->delete('relParticipation')
		    ->where('kid = %i AND id_event = %i', $kid, $id_event)
		    ->execute();
	} catch(DibiException $x) {
	    throw new \Nette\IOException($e->getMessage());
	}
    }
    
    /**
     * Inserts record into relParticipation table
     * @param \florbalMohelnice\Entities\Participation $part
     * @return type
     * @throws \Nette\IOException
     */
    public function createParticipation(Participation $part) {
	$this->participIntegrityTest($part);
	try {
	    $this->connection->insert('relParticipation', $part)
		    ->execute();
	} catch(DibiException $x) {
	    throw new \Nette\IOException($x->getMessage(), $x);
	} catch(\DibiDriverException $x) {
	    throw new \Nette\IOException($x->getMessage(), 1);
	}
    }
    
    /**
     * Updates Participation record specified by argument
     * @param \florbalMohelnice\Entities\Participation $part
     * @throws \Nette\IOException
     */
    public function updateParticipation(Participation $part) {
	$this->participIntegrityTest($part);
	$kid = $part->offsetGet('kid');
	$part->offsetUnset('kid');
	$id_event = $part->offsetGet('id_event');
	$part->offsetUnset('id_event');
	try {
	    $this->connection->update('relParticipation', $part)
		    ->where('kid = %i AND id_event = %i', $kid, $id_event)
		    ->execute();
	} catch (DibiException $x) {
	    throw new \Nette\IOException($x->getMessage(), $x);
	}
    }
    
    public function deleteParticipation(Participation $part) {
	$kid = $part->offsetGet('kid');
	$idEvent = $part->offsetGet('id_event');
	try {
	    $this->connection->delete('relParticipation')
		    ->where('kid = %i AND id_event = %i', $kid, $idEvent)
		    ->execute();
	} catch (DibiException $e) {
	    throw new \Nette\IOException($e->getMessage(), $e->getCode(), $e);
	} catch (\DibiDriverException $e) {
	    throw new \Nette\IOException($e->getMessage(), $e->getCode(), $e);
	}
    }
}