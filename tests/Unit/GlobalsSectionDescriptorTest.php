<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\GlobalsSectionDescriptor;
use PHPUnit\Framework\TestCase;

class GlobalsSectionDescriptorTest extends TestCase
{
    public function testAllPropertiesAreAccessible(): void
    {
        $descriptor = new GlobalsSectionDescriptor(
            sectionName: 'OpenCoreEMR Test Module',
            moduleDirName: 'oce-module-test',
            enableKey: 'oce_test_module_enabled',
            settingsDescription: 'Configure API credentials and module options.',
        );

        $this->assertSame('OpenCoreEMR Test Module', $descriptor->sectionName);
        $this->assertSame('oce-module-test', $descriptor->moduleDirName);
        $this->assertSame('oce_test_module_enabled', $descriptor->enableKey);
        $this->assertSame('Configure API credentials and module options.', $descriptor->settingsDescription);
    }
}
