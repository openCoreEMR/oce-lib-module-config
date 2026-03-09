<?php

/**
 * Factory for creating configuration accessors
 *
 * @package   OpenCoreEMR
 * @link      https://opencoreemr.com
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc
 * @license   GNU General Public License 3
 */

namespace OpenCoreEMR\ModuleConfig;

/**
 * Factory for creating the appropriate configuration accessor.
 *
 * Configuration sources in precedence order:
 *   1. YAML files (with secret resolution and env var overrides) - FileConfigAccessor
 *   2. Environment variables only - EnvironmentConfigAccessor
 *   3. Database globals - GlobalsAccessor
 */
class ConfigFactory
{
    public function __construct(
        private readonly ModuleConfigDescriptor $descriptor,
        private readonly ?SecretProviderInterface $secretProvider = null,
    ) {
    }

    /**
     * Check if environment-only config mode is enabled
     */
    public function isEnvConfigMode(): bool
    {
        $value = getenv($this->descriptor->envConfigVar);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if YAML file config mode is active (any config files exist)
     */
    public function isFileConfigMode(): bool
    {
        $loader = new YamlConfigLoader($this->secretProvider);
        return $loader->hasConfigFiles($this->getConfigFileCandidates());
    }

    /**
     * Check if any external config mode is active (file or env)
     */
    public function isExternalConfigMode(): bool
    {
        return $this->isFileConfigMode() || $this->isEnvConfigMode();
    }

    /**
     * Create the appropriate config accessor based on environment
     *
     * Precedence: file config > env config > database
     */
    public function createConfigAccessor(): ConfigAccessorInterface
    {
        if ($this->isFileConfigMode()) {
            $loader = new YamlConfigLoader($this->secretProvider);
            $paths = $loader->resolveFilePaths($this->getConfigFileCandidates());
            $data = $loader->load($paths);
            return new FileConfigAccessor($data, $this->descriptor);
        }

        if ($this->isEnvConfigMode()) {
            return new EnvironmentConfigAccessor($this->descriptor);
        }

        return new GlobalsAccessor();
    }

    /**
     * Get candidate config file paths (overridden or conventional)
     *
     * @return list<string>
     */
    private function getConfigFileCandidates(): array
    {
        $paths = [];

        $configFile = getenv($this->descriptor->configFileEnvVar);
        $paths[] = $configFile !== false ? $configFile : $this->descriptor->conventionalConfigPath;

        $secretsFile = getenv($this->descriptor->secretsFileEnvVar);
        $paths[] = $secretsFile !== false ? $secretsFile : $this->descriptor->conventionalSecretsPath;

        return $paths;
    }
}
