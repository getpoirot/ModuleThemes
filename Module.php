<?php
// Module load without composer or pre-register autoload
// we need some class of module to be available to module
$loader = new \Poirot\Loader\Autoloader\LoaderAutoloadNamespace;
$loader->setResources([
    'Module\\Themes' => [ __DIR__.'/src/' ],
]);

$loader->register(true); // true for prepend loader in autoload stack


// Load module in cases that namespace not resolved by php class autoload
//
require_once __DIR__ . '/src/Module.php';
