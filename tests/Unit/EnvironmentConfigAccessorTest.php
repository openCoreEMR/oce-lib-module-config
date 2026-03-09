<?php

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\EnvironmentConfigAccessor;
use OpenCoreEMR\ModuleConfig\ModuleConfigDescriptor;
use PHPUnit\Framework\TestCase;

class EnvironmentConfigAccessorTest extends TestCase
{
    private ModuleConfigDescriptor $descriptor;

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

    public function testGetStringFromEnvVar(): void
    {
        putenv('OCE_TEST_MODULE_PROJECT_ID=env-proj');
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertSame('env-proj', $accessor->getString('oce_test_module_project_id'));
    }

    public function testGetBooleanFromEnvVar(): void
    {
        putenv('OCE_TEST_MODULE_ENABLED=1');
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertTrue($accessor->getBoolean('oce_test_module_enabled'));
    }

    public function testGetBooleanFalseFromEnvVar(): void
    {
        putenv('OCE_TEST_MODULE_ENABLED=0');
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertFalse($accessor->getBoolean('oce_test_module_enabled'));
    }

    public function testGetBooleanTrueString(): void
    {
        putenv('OCE_TEST_MODULE_ENABLED=true');
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertTrue($accessor->getBoolean('oce_test_module_enabled'));
    }

    public function testGetIntFromEnvVar(): void
    {
        putenv('OCE_TEST_MODULE_RETRY_COUNT=3');
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertSame(3, $accessor->getInt('oce_test_module_retry_count'));
    }

    public function testReturnsDefaultWhenEnvVarNotSet(): void
    {
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertSame('fallback', $accessor->getString('oce_test_module_project_id', 'fallback'));
    }

    public function testHasReturnsTrueWhenEnvVarSet(): void
    {
        putenv('OCE_TEST_MODULE_PROJECT_ID=abc');
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertTrue($accessor->has('oce_test_module_project_id'));
    }

    public function testHasReturnsFalseWhenEnvVarNotSet(): void
    {
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertFalse($accessor->has('oce_test_module_project_id'));
    }

    public function testDelegatesToGlobalsForNonModuleKeys(): void
    {
        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        // Non-module keys delegate to GlobalsAccessor; in test context returns default
        $this->assertSame('', $accessor->getString('OE_SITE_DIR', ''));
    }

    public function testAllEnvVarsAreMapped(): void
    {
        putenv('OCE_TEST_MODULE_ENABLED=1');
        putenv('OCE_TEST_MODULE_PROJECT_ID=proj');
        putenv('OCE_TEST_MODULE_API_KEY=key');
        putenv('OCE_TEST_MODULE_API_SECRET=secret');
        putenv('OCE_TEST_MODULE_REGION=us');
        putenv('OCE_TEST_MODULE_RETRY_COUNT=5');

        $accessor = new EnvironmentConfigAccessor($this->descriptor);

        $this->assertTrue($accessor->getBoolean('oce_test_module_enabled'));
        $this->assertSame('proj', $accessor->getString('oce_test_module_project_id'));
        $this->assertSame('key', $accessor->getString('oce_test_module_api_key'));
        $this->assertSame('secret', $accessor->getString('oce_test_module_api_secret'));
        $this->assertSame('us', $accessor->getString('oce_test_module_region'));
        $this->assertSame(5, $accessor->getInt('oce_test_module_retry_count'));
    }
}
