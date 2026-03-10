<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

/**
 * Parameterize generic config classes with module-specific key maps, paths, and env var names.
 *
 * Each module provides a descriptor instead of copy-pasting config accessor classes.
 */
final readonly class ModuleConfigDescriptor
{
    /**
     * Reverse key map: internal config key => short YAML key
     *
     * @var array<string, string>
     */
    public array $reverseKeyMap;

    /**
     * @param array<string, string> $yamlKeyMap
     *   Short YAML key => internal config key (e.g. 'api_secret' => 'oce_sinch_fax_api_secret')
     * @param array<string, string> $envOverrideMap
     *   Internal config key => env var name (e.g. 'oce_sinch_fax_api_secret' => 'OCE_SINCH_FAX_API_SECRET')
     * @param string $envConfigVar
     *   Env var that enables env-only config mode (e.g. 'OCE_SINCH_FAX_ENV_CONFIG')
     * @param string $conventionalConfigPath
     *   Default config file path (e.g. '/etc/oce/sinch-fax/config.yaml')
     * @param string $conventionalSecretsPath
     *   Default secrets file path (e.g. '/etc/oce/sinch-fax/secrets.yaml')
     * @param string $configFileEnvVar
     *   Env var to override config file path (e.g. 'OCE_SINCH_FAX_CONFIG_FILE')
     * @param string $secretsFileEnvVar
     *   Env var to override secrets file path (e.g. 'OCE_SINCH_FAX_SECRETS_FILE')
     */
    public function __construct(
        public array $yamlKeyMap,
        public array $envOverrideMap,
        public string $envConfigVar,
        public string $conventionalConfigPath,
        public string $conventionalSecretsPath,
        public string $configFileEnvVar,
        public string $secretsFileEnvVar,
    ) {
        $this->reverseKeyMap = array_flip($yamlKeyMap);
    }
}
