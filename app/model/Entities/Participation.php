<?php

namespace florbalMohelnice\Entities;

class Participation extends \DibiRow {
    
	const YES_GOING_OWN = 'ybh';
	const YES_GOING_ADM = 'yba';
	const NO_NOT_GOING_OWN = 'nbh';
    
    
    public function __construct($arr) {
	parent::__construct($arr);
    }
}

