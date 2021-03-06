<?php
use \Nette\Forms\Form,
	Nette\Config\Configurator,
	Vodacek\Forms\Controls\DateInput;
	
// Load Nette Framework or autoloader generated by Composer
require __DIR__ . '/../libs/autoload.php';

$configurator = new Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->setDebugMode(TRUE);
$configurator->enableDebugger(__DIR__ . '/../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__ . '/../temp');
$loader = $configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../libs')
	->register();

// pridam dibi rozsireni
$configurator->onCompile[] = function ($configurator, $compiler) {
    $compiler->addExtension('dibi', new DibiNetteExtension);
};

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon', $configurator::NONE); // none section
$container = $configurator->createContainer();

// form addons register
DateInput::register();
Kdyby\Replicator\Container::register();

$_SESSION['__NF']['META'] = array(); // due to session debug panel

// service panel register
Panel\ServicePanel::register($container, $loader);

return $container;
