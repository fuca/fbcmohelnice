<?php

define('WWW_DIR', dirname(__FILE__));

define('FM_DATA_DIR', WWW_DIR.'/files');

// absolute filesystem path to the cache storage
define('CACHE_DIR', APP_DIR . '/cache');

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../libs');

// absolute filesystem path to the images
define ('IMAGES_DIR', WWW_DIR . '/images');

// absolute filesystem path to the file manager thumbnails
define ('FM_IMAGES_DIR', IMAGES_DIR.'/fileManager');

// absolute filesystem path to the article images 
define ('ARTICLES_IDIR', IMAGES_DIR . '/articles');

// absolute filesystem path to the gallery images 
define ('GALLERY_IDIR', IMAGES_DIR . '/gallery');

// absolute filesystem path to the users images 
define ('USERS_IDIR', IMAGES_DIR . '/users');

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

// Let bootstrap create Dependency Injection container.
$container = require __DIR__ . '/../app/bootstrap.php';

// Run application.
$container->application->run();
