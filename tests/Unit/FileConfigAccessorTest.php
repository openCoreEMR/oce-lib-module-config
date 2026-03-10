<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\FileConfigAccessor;
use OpenCoreEMR\ModuleConfig\ModuleConfigDescriptor;
use PHPUnit\Framework\TestCase;

class FileConfigAccessorTest extends TestCase
{
    private ModuleConfigDescriptor $descriptor; // @phpstan-ignore property.uninitialized (setUp)

    protected function setUp(): void
    {
        $this->descriptor = TestDescriptor::create();
        $this->clearEnvVars();
    }

    protected function tearDown(): void
    {
        $this->clearEnvVars();
    }

    private function clearEnvVars(): void
    {
        foreach (TestDescriptor::allEnvVars() as $var) {
            putenv($var);
        }
    }

    public function testGetStringFromYamlData(): void
    {
        $accessor = new FileConfigAccessor(
            ['project_id' => 'yaml-project-123'],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertSame(
            'yaml-project-123',
            $accessor->getString('oce_test_module_project_id')
        );
    }

    public function testGetBooleanFromYamlData(): void
    {
        $accessor = new FileConfigAccessor(
            ['enabled' => true],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertTrue($accessor->getBoolean('oce_test_module_enabled'));
    }

    public function testGetIntFromYamlData(): void
    {
        $accessor = new FileConfigAccessor(
            ['retry_count' => 7],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertSame(7, $accessor->getInt('oce_test_module_retry_count'));
    }

    public function testReturnsDefaultWhenKeyNotInYaml(): void
    {
        $accessor = new FileConfigAccessor([], $this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertSame(
            'fallback',
            $accessor->getString('oce_test_module_project_id', 'fallback')
        );
    }

    public function testHasReturnsTrueForYamlKey(): void
    {
        $accessor = new FileConfigAccessor(
            ['project_id' => 'abc'],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertTrue($accessor->has('oce_test_module_project_id'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $accessor = new FileConfigAccessor([], $this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertFalse($accessor->has('oce_test_module_project_id'));
    }

    public function testEnvVarOverridesYamlValue(): void
    {
        putenv('OCE_TEST_MODULE_PROJECT_ID=env-project-456');

        $accessor = new FileConfigAccessor(
            ['project_id' => 'yaml-project-123'],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertSame(
            'env-project-456',
            $accessor->getString('oce_test_module_project_id')
        );
    }

    public function testEnvVarOverridesYamlBoolean(): void
    {
        putenv('OCE_TEST_MODULE_ENABLED=0');

        $accessor = new FileConfigAccessor(
            ['enabled' => true],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertFalse($accessor->getBoolean('oce_test_module_enabled'));
    }

    public function testYamlValueUsedWhenEnvVarNotSet(): void
    {
        $accessor = new FileConfigAccessor(
            ['region' => 'eu1'],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertSame(
            'eu1',
            $accessor->getString('oce_test_module_region')
        );
    }

    public function testAllYamlKeysAreMapped(): void
    {
        $yamlData = [
            'enabled' => true,
            'project_id' => 'proj-123',
            'api_key' => 'key-789',
            'api_secret' => 'secret',
            'region' => 'use1',
            'retry_count' => 5,
        ];

        $accessor = new FileConfigAccessor($yamlData, $this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($accessor->getBoolean('oce_test_module_enabled'));
        $this->assertSame('proj-123', $accessor->getString('oce_test_module_project_id'));
        $this->assertSame('key-789', $accessor->getString('oce_test_module_api_key'));
        $this->assertSame('secret', $accessor->getString('oce_test_module_api_secret'));
        $this->assertSame('use1', $accessor->getString('oce_test_module_region'));
        $this->assertSame(5, $accessor->getInt('oce_test_module_retry_count'));
    }

    public function testUnknownYamlKeysAreIgnored(): void
    {
        $accessor = new FileConfigAccessor(
            ['project_id' => 'abc', 'unknown_key' => 'should-be-ignored'],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        $this->assertSame('abc', $accessor->getString('oce_test_module_project_id'));
    }

    public function testGetDelegatesToGlobalsForNonModuleKeys(): void
    {
        $accessor = new FileConfigAccessor(
            ['project_id' => 'abc'],
            $this->descriptor,
            TestDescriptor::emptyGlobalsBag(),
        );

        // OE_SITE_DIR delegates to GlobalsAccessor; in test context returns default
        $this->assertSame('', $accessor->getString('OE_SITE_DIR', ''));
    }
}
