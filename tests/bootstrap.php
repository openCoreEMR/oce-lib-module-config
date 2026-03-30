<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Suppress error_log output during tests
ini_set('error_log', '/dev/null');

// Load mock classes to shadow real OpenEMR classes (must come after autoloader)
require_once __DIR__ . '/Mocks/MockQueryUtils.php';
require_once __DIR__ . '/Mocks/MockCryptoGen.php';
require_once __DIR__ . '/Mocks/openemr_functions.php';
