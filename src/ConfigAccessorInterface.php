<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

/**
 * Abstraction for configuration access, matching Symfony ParameterBag's typed accessor methods.
 * Allows configuration to be read from database globals, environment variables, or YAML files.
 */
interface ConfigAccessorInterface
{
    /**
     * Get a string configuration value
     */
    public function getString(string $key, string $default = ''): string;

    /**
     * Get a boolean configuration value
     */
    public function getBoolean(string $key, bool $default = false): bool;

    /**
     * Get an integer configuration value
     */
    public function getInt(string $key, int $default = 0): int;

    /**
     * Check if a configuration key exists
     */
    public function has(string $key): bool;
}
