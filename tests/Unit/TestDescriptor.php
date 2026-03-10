<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\ModuleConfigDescriptor;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Build a sinch-fax-style descriptor for use in tests
 */
final class TestDescriptor
{
    public static function create(): ModuleConfigDescriptor
    {
        return new ModuleConfigDescriptor(
            yamlKeyMap: [
                'enabled' => 'oce_test_module_enabled',
                'project_id' => 'oce_test_module_project_id',
                'api_key' => 'oce_test_module_api_key',
                'api_secret' => 'oce_test_module_api_secret',
                'region' => 'oce_test_module_region',
                'retry_count' => 'oce_test_module_retry_count',
            ],
            envOverrideMap: [
                'oce_test_module_enabled' => 'OCE_TEST_MODULE_ENABLED',
                'oce_test_module_project_id' => 'OCE_TEST_MODULE_PROJECT_ID',
                'oce_test_module_api_key' => 'OCE_TEST_MODULE_API_KEY',
                'oce_test_module_api_secret' => 'OCE_TEST_MODULE_API_SECRET',
                'oce_test_module_region' => 'OCE_TEST_MODULE_REGION',
                'oce_test_module_retry_count' => 'OCE_TEST_MODULE_RETRY_COUNT',
            ],
            envConfigVar: 'OCE_TEST_MODULE_ENV_CONFIG',
            conventionalConfigPath: '/etc/oce/test-module/config.yaml',
            conventionalSecretsPath: '/etc/oce/test-module/secrets.yaml',
            configFileEnvVar: 'OCE_TEST_MODULE_CONFIG_FILE',
            secretsFileEnvVar: 'OCE_TEST_MODULE_SECRETS_FILE',
        );
    }

    /**
     * Create an empty ParameterBag for use as a globals stub in tests
     */
    public static function emptyGlobalsBag(): ParameterBag
    {
        return new ParameterBag();
    }

    /**
     * All env vars used in the test descriptor, for cleanup
     *
     * @return list<string>
     */
    public static function allEnvVars(): array
    {
        return [
            'OCE_TEST_MODULE_ENABLED',
            'OCE_TEST_MODULE_PROJECT_ID',
            'OCE_TEST_MODULE_API_KEY',
            'OCE_TEST_MODULE_API_SECRET',
            'OCE_TEST_MODULE_REGION',
            'OCE_TEST_MODULE_RETRY_COUNT',
            'OCE_TEST_MODULE_ENV_CONFIG',
            'OCE_TEST_MODULE_CONFIG_FILE',
            'OCE_TEST_MODULE_SECRETS_FILE',
        ];
    }
}
