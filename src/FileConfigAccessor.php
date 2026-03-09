<?php

/**
 * File-based configuration accessor (YAML config files)
 *
 * @package   OpenCoreEMR
 * @link      https://opencoreemr.com
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc
 * @license   GNU General Public License 3
 */

namespace OpenCoreEMR\ModuleConfig;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Read module configuration from YAML files, with env var overrides.
 *
 * Precedence: environment variables > YAML files (with resolved secrets) > defaults.
 * OpenEMR system values (OE_SITE_DIR, webroot, etc.) delegate to GlobalsAccessor.
 *
 * @internal Use ConfigFactory::createConfigAccessor() instead of instantiating directly
 */
class FileConfigAccessor implements ConfigAccessorInterface
{
    /** @var ParameterBag<string, mixed> */
    private readonly ParameterBag $bag;
    private readonly GlobalsAccessor $globalsAccessor;

    /**
     * @param array<string, mixed> $yamlData   Merged data from YamlConfigLoader::load() (secrets already resolved)
     * @param ModuleConfigDescriptor $descriptor Module-specific key maps and env var names
     */
    public function __construct(array $yamlData, private readonly ModuleConfigDescriptor $descriptor)
    {
        $this->globalsAccessor = new GlobalsAccessor();
        $this->bag = $this->buildBag($yamlData);
    }

    /**
     * Build a ParameterBag from YAML data with env var overrides
     *
     * Start with YAML values (mapped to internal keys), then override with
     * any set environment variables.
     *
     * @param array<string, mixed> $yamlData
     * @return ParameterBag<string, mixed>
     */
    private function buildBag(array $yamlData): ParameterBag
    {
        $params = [];

        // Map short YAML keys to internal config keys
        foreach ($this->descriptor->yamlKeyMap as $yamlKey => $configKey) {
            if (array_key_exists($yamlKey, $yamlData)) {
                $params[$configKey] = $yamlData[$yamlKey];
            }
        }

        // Override with environment variables where set
        foreach ($this->descriptor->envOverrideMap as $configKey => $envVar) {
            $value = getenv($envVar);
            if ($value !== false) {
                $params[$configKey] = $value;
            }
        }

        return new ParameterBag($params);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->descriptor->reverseKeyMap[$key])) {
            return $this->bag->get($key, $default);
        }

        return $this->globalsAccessor->get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        if (isset($this->descriptor->reverseKeyMap[$key])) {
            return $this->bag->getString($key, $default);
        }

        return $this->globalsAccessor->getString($key, $default);
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        if (isset($this->descriptor->reverseKeyMap[$key])) {
            return $this->bag->getBoolean($key, $default);
        }

        return $this->globalsAccessor->getBoolean($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        if (isset($this->descriptor->reverseKeyMap[$key])) {
            return $this->bag->getInt($key, $default);
        }

        return $this->globalsAccessor->getInt($key, $default);
    }

    public function has(string $key): bool
    {
        if (isset($this->descriptor->reverseKeyMap[$key])) {
            return $this->bag->has($key);
        }

        return $this->globalsAccessor->has($key);
    }
}
