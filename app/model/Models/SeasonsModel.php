<?php

namespace florbalMohelnice\Models;

use florbalMohelnice\Entities\Season;

/**
 * Description of SeasonsModel
 *
 * @author Michal Fučík <michal.fuca.fucik@gmail.com>
 * @package florbalMohelnice
 */
class SeasonsModel extends BaseModel {

    public function getFluent() {
	return $this->connection->select('*')->from('EligibleSeasons');
    }

    public function getSelectSeasons() {
	$res = $this->connection->select('id_season,label')->from('EligibleSeasons')
			->orderBy('label')->desc()
			->execute()->fetchPairs();
	return $res;
    }

    public function getSelectSeasonsWithoutApplications($kid) {
	if ($kid == NULL)
	    throw new \Nette\InvalidArgumentException("Argument kid was null");
	if (!is_numeric($kid))
	    throw new \Nette\InvalidArgumentException("Argument kid has to be type of numeric");

	try {
	    $res = $this->connection->select('EligibleSeasons.id_season, EligibleSeasons.label')
			    ->from('EligibleSeasons')
			    ->leftJoin($this->connection->select('id_season, kid')
				    ->from('SeasonApplications')
				    ->where('kid = %i', $kid))->as('apps')
			    ->using('(id_season)')
			    ->where('kid IS NULL')
			    ->execute()->fetchPairs();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $res;
    }

    public function getSeason($id_season) {
	if (!is_numeric($id_season))
	    throw new \Nette\InvalidArgumentException("Argument id_season has to be type of numeric");

	try {
	    $s = $this->connection->select('EligibleSeasons.*')
		    ->from('EligibleSeasons')
		    ->where('id_season = %i', $id_season)
		    ->execute()->setRowClass('florbalMohelnice\Entities\Season')
		    ->fetch();
	    $taxes = $this->connection->select('SeasonTaxes.*')
			    ->from('SeasonTaxes')
			    ->where('id_season = %i', $id_season)
			    ->execute()->fetchAssoc("id_group");
	    if ($s)
		
		$s->offsetSet('common', $taxes);
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	}
	return $s;
    }

    public function createSeason(Season $season) {
	if ($season == NULL)
	    throw new \Nette\InvalidArgumentException("Argument season was null");
	$common = $season->offsetGet('common');
	$season->offsetUnset('common');
	$idSeason = date('Y', $season->offsetGet('date_from')->getTimestamp());
	$label = $idSeason ." / ".($idSeason+1);
	$season->offsetSet('label', $label);
	$season->offsetSet('id_season', $idSeason);
	try {
	    $this->connection->begin();
	    if ($season->offsetGet('active'))
		$this->connection->update('EligibleSeasons', array('active' => 0))
			->execute();

	    $this->connection->insert('EligibleSeasons', $season)
		    ->execute();
	    $this->_createSeasonTaxes($common, $idSeason);
	    $this->connection->commit();
	} catch (DibiException $ex) {
	    throw new \Nette\IOException($ex->getMessage());
	} catch (\DibiDriverException $ex) {
	    throw new \Nette\IOException($ex->getMessage(), $ex->getCode(), $ex);
	}
	return $idSeason;
    }

    public function updateSeason(Season $season) {
	if ($season == NULL)
	    throw new \Nette\InvalidArgumentException("Argument season was null");
	$common = $season->offsetGet('common');
	$season->offsetUnset('common');
	$idSeason = $season->offsetGet('id_season');

	try {
	    $this->connection->begin();
	    if ($season->offsetGet('active'))
		$this->connection->update('EligibleSeasons', array('active' => 0))
			->execute();

	    $this->connection->update('EligibleSeasons', $season)
		    ->where('id_season = %i', $idSeason)
		    ->execute();
	    $this->_removeSesonTaxes($idSeason);
	    $this->_createSeasonTaxes($common, $idSeason);

	    $this->connection->commit();
	} catch (DibiException $ex) {
	    throw new Exception($ex->getMessage());
	}
    }

    public function deleteSeason(Season $season) {
	if ($season == NULL)
	    throw new \Nette\InvalidArgumentException("Argument season was null");
	try {
	    $idSeason = $season->offsetGet('id_season');
	    $this->connection->begin();
	    $this->connection->delete('EligibleSeasons')
		    ->where('id_season = %i', $idSeason)
		    ->execute();
	    $this->connection->delete('SeasonTaxes')
		    ->where('id_season = %i', $idSeason)
		    ->execute();
	    $this->connection->commit();
	} catch (DibiException $ex) {
	    throw new Exception($ex->getMessage());
	}
    }

    private function _createSeasonTaxes($rows, $id) {
	foreach ($rows as $key => $c) {
	    $this->connection->insert('SeasonTaxes', array(
			'id_season' => $id,
			'id_group' => $key,
			'credit' => $c['credit'],
			'clp' => $c['clp']))
		    ->execute();
	}
    }

    private function _removeSesonTaxes($id) {
	if (!is_numeric($id))
	    throw new \Nette\InvalidArgumentException('Argument id has to be type of numeric');
	$this->connection->delete('SeasonTaxes')
		->where('id_season = %i', $id)
		->execute();
    }

}

