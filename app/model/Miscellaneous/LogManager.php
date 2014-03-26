<?php
namespace florbalMohelnice\Miscellaneous\Logging;
use florbalMohelnice\Miscellaneous\Logging\ILogger;

/**
 * Description of LogManager
 *
 * @author fuca
 */
final class LogManager extends \Nette\Object implements ILogManager {
    
    private $actionLogger;
    private $errorLogger;

    public static function createLogManager (ILogger $actionLogger, ILogger $dbLogger) {
	$man = new LogManager($actionLogger, $dbLogger);
	return $man;
    }
    
    public function __construct($actionLogger, $dbLogger) {
	$this->setActionLogger($actionLogger);
	$this->setErrorLogger($dbLogger);
    }
    
    public function getActionLogger() {
	if (!isset($this->actionLogger))
		throw new Nette\InvalidStateException("Attribute is not set");
	return $this->actionLogger;
    }
    
    public function getErrorLogger() {
	if (!isset($this->errorLogger))
		throw new \Nette\InvalidStateException("Attribute is not set");
	return $this->errorLogger;
    }
    
    public function setActionLogger(florbalMohelnice\Miscellaneous\Logging\ILogger $logger) {
	if ($logger == NULL)
	    throw new \Nette\InvalidArgumentException("Argument can't be NULL");
	$this->ActionLogger = $logger;
    }
    
    public function setErrorLogger(florbalMohelnice\Miscellaneous\Logging\ILogger $logger) {
	if ($logger == NULL)
	    throw new \Nette\InvalidArgumentException("Argument can't be NULL");
	$this->errorLogger = $logger;
    }

    public function logAction($level, $message) {
	
	$this->getActionLogger($level, $message);
    }
    
    public function logError($level, $message) {

	$this->getErrorLogger($level, $message);
    }

}

