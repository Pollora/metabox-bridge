<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/*
 * ARCH-07: the harness preset (WordPress classes and functions, the tests), plus what is specific to this package.
 */
$configuration = require __DIR__.'/vendor/pollora/harness/presets/composer-dependency-analyser.php';

return $configuration
    // The bridge runs inside Pollora: the framework brings Laravel, the attributes, the discovery engine and its structure discoverer.
    ->ignoreErrorsOnPackages(['laravel/framework', 'pollora/attributes', 'pollora/discovery', 'spatie/php-structure-discoverer'], [ErrorType::SHADOW_DEPENDENCY])
    // pollora/framework is the host the bridge is wired into at run time; the code only names the classes the framework brings.
    ->ignoreErrorsOnPackage('pollora/framework', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]);
