<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\ConfigService;
use OpenEMR\Common\Database\QueryUtils;
use PHPUnit\Framework\TestCase;

class ConfigServiceTest extends TestCase
{
    private ConfigService $service; // @phpstan-ignore property.uninitialized (setUp)

    protected function setUp(): void
    {
        QueryUtils::reset();
        $this->service = new ConfigService();
    }

    protected function tearDown(): void
    {
        QueryUtils::reset();
    }

    public function testSaveSettingExecutesUpsert(): void
    {
        $this->service->saveSetting('oce_test_key', 'test_value');

        $queries = QueryUtils::getQueries();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('INSERT INTO `globals`', $queries[0]['sql']);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $queries[0]['sql']);
        $this->assertSame(['oce_test_key', 'test_value', 'test_value'], $queries[0]['binds']);
    }

    public function testSaveEncryptedSettingEncryptsBeforeSaving(): void
    {
        $this->service->saveEncryptedSetting('oce_secret_key', 'secret_value');

        $queries = QueryUtils::getQueries();
        $this->assertCount(1, $queries);
        $this->assertSame('oce_secret_key', $queries[0]['binds'][0]);
        // MockCryptoGen uses base64; value is bound twice for INSERT and UPDATE
        $encrypted = base64_encode('secret_value');
        $this->assertSame($encrypted, $queries[0]['binds'][1]);
        $this->assertSame($encrypted, $queries[0]['binds'][2]);
    }

    public function testSaveSettingPropagatesExceptions(): void
    {
        QueryUtils::setNextException(new \RuntimeException('DB error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        $this->service->saveSetting('oce_test_key', 'value');
    }

    public function testMultipleSaveSettingCallsAreRecorded(): void
    {
        $this->service->saveSetting('key_a', 'val_a');
        $this->service->saveSetting('key_b', 'val_b');

        $queries = QueryUtils::getQueries();
        $this->assertCount(2, $queries);
        $this->assertSame(['key_a', 'val_a', 'val_a'], $queries[0]['binds']);
        $this->assertSame(['key_b', 'val_b', 'val_b'], $queries[1]['binds']);
    }
}
