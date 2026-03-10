<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Suppress error_log output during tests
ini_set('error_log', '/dev/null');
