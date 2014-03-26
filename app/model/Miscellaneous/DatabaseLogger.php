<?php
namespace florbalMohelnice\Miscellaneous\Logging;

/**
 * Description of DatabaseLogger
 *
 * @author fuca
 */
class DatabaseLogger extends \Nette\Object implements ILogger {

    private $model;
    
    public function setModel(florbalMohelnice\Miscellaneous\Logging\IModel $model) {
	if ($model == NULL)
	    throw new \Nette\InvalidArgumentException("");
    }
    
    public static function createDatabaseLogger($options = array()) {
	return new DatabaseLogger();
    }
    
    public function logMessage($level, $message = NULL) {
	
    }

    public function setMinimumLogLevel($level) {
	
    }

}
