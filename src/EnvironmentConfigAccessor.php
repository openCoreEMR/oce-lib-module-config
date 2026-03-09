<?php

/**
 * Environment-based configuration accessor
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
 * Read module configuration from environment variables.
 *
 * Used when the module's env config var is set (e.g. OCE_SINCH_FAX_ENV_CONFIG=1),
 * bypassing the database-backed globals system entirely for module config.
 * OpenEMR system values (OE_SITE_DIR, webroot, etc.) delegate to GlobalsAccessor.
 *
 * @internal Use ConfigFactory::createConfigAccessor() instead of instantiating directly
 */
class EnvironmentConfigAccessor implements ConfigAccessorInterface
{
    /** @var ParameterBag<string, mixed> */
    private readonly ParameterBag $envBag;
    private readonly GlobalsAccessor $globalsAccessor;

    /**
     * @var array<string, string> internal config key => env var name
     */
    private readonly array $envOverrideMap;

    public function __construct(ModuleConfigDescriptor $descriptor, ParameterBag $globalsBag)
    {
        $this->envOverrideMap = $descriptor->envOverrideMap;
        $this->globalsAccessor = new GlobalsAccessor($globalsBag);
        $this->envBag = $this->buildEnvBag();
    }

    /**
     * @return ParameterBag<string, mixed>
     */
    private function buildEnvBag(): ParameterBag
    {
        $params = [];
        foreach ($this->envOverrideMap as $configKey => $envVar) {
            $value = getenv($envVar);
            if ($value !== false) {
                $params[$configKey] = $value;
            }
        }
        return new ParameterBag($params);
    }

    public function getString(string $key, string $default = ''): string
    {
        if (isset($this->envOverrideMap[$key])) {
            return $this->envBag->getString($key, $default);
        }

        return $this->globalsAccessor->getString($key, $default);
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        if (isset($this->envOverrideMap[$key])) {
            return $this->envBag->getBoolean($key, $default);
        }

        return $this->globalsAccessor->getBoolean($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        if (isset($this->envOverrideMap[$key])) {
            return $this->envBag->getInt($key, $default);
        }

        return $this->globalsAccessor->getInt($key, $default);
    }

    public function has(string $key): bool
    {
        if (isset($this->envOverrideMap[$key])) {
            return $this->envBag->has($key);
        }

        return $this->globalsAccessor->has($key);
    }
}
