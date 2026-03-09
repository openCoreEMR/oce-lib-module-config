<?php

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\Exception\SecretResolutionException;
use OpenCoreEMR\ModuleConfig\GcpSecretManagerProvider;
use PHPUnit\Framework\TestCase;

class GcpSecretManagerProviderTest extends TestCase
{
    public function testThrowsWhenGcsmLibraryNotInstalled(): void
    {
        // In test environment, google/cloud-secret-manager is not installed
        $provider = new GcpSecretManagerProvider();

        $this->expectException(SecretResolutionException::class);
        $this->expectExceptionMessage('google/cloud-secret-manager is not installed');

        $provider->getSecret('my_secret', 'my_project');
    }
}
