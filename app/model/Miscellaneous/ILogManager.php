<?php
namespace florbalMohelnice\Miscellaneous\Logging;

interface ILogManager {
    	
	function logAction($level, $message);
	function logError($level, $message);
}
