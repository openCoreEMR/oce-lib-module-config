<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\ModuleConfigDescriptor;
use PHPUnit\Framework\TestCase;

class ModuleConfigDescriptorTest extends TestCase
{
    public function testReverseKeyMapIsDerivedFromYamlKeyMap(): void
    {
        $descriptor = new ModuleConfigDescriptor(
            yamlKeyMap: ['short_key' => 'internal_key', 'other' => 'internal_other'],
            envOverrideMap: ['internal_key' => 'ENV_KEY'],
            envConfigVar: 'ENV_CONFIG',
            conventionalConfigPath: '/etc/config.yaml',
            conventionalSecretsPath: '/etc/secrets.yaml',
            configFileEnvVar: 'CONFIG_FILE',
            secretsFileEnvVar: 'SECRETS_FILE',
        );

        $this->assertSame('short_key', $descriptor->reverseKeyMap['internal_key']);
        $this->assertSame('other', $descriptor->reverseKeyMap['internal_other']);
    }

    public function testThrowsOnDuplicateYamlKeyMapValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('yamlKeyMap values must be unique');

        new ModuleConfigDescriptor(
            yamlKeyMap: ['key_a' => 'same_internal', 'key_b' => 'same_internal'],
            envOverrideMap: ['same_internal' => 'ENV_VAR'],
            envConfigVar: 'ENV_CONFIG',
            conventionalConfigPath: '/etc/config.yaml',
            conventionalSecretsPath: '/etc/secrets.yaml',
            configFileEnvVar: 'CONFIG_FILE',
            secretsFileEnvVar: 'SECRETS_FILE',
        );
    }

    public function testAllPropertiesAreAccessible(): void
    {
        $descriptor = TestDescriptor::create();

        $this->assertNotEmpty($descriptor->yamlKeyMap);
        $this->assertNotEmpty($descriptor->envOverrideMap);
        $this->assertNotEmpty($descriptor->reverseKeyMap);
        $this->assertSame('OCE_TEST_MODULE_ENV_CONFIG', $descriptor->envConfigVar);
        $this->assertSame('/etc/oce/test-module/config.yaml', $descriptor->conventionalConfigPath);
        $this->assertSame('/etc/oce/test-module/secrets.yaml', $descriptor->conventionalSecretsPath);
        $this->assertSame('OCE_TEST_MODULE_CONFIG_FILE', $descriptor->configFileEnvVar);
        $this->assertSame('OCE_TEST_MODULE_SECRETS_FILE', $descriptor->secretsFileEnvVar);
    }
}
