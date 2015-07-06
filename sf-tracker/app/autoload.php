<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
$loader->register(array(
    'Elendev'              => __DIR__.'/../src'
));
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
