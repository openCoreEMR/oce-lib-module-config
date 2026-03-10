<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\ConfigFactory;
use OpenCoreEMR\ModuleConfig\EnvironmentConfigAccessor;
use OpenCoreEMR\ModuleConfig\FileConfigAccessor;
use OpenCoreEMR\ModuleConfig\GlobalsAccessor;
use OpenCoreEMR\ModuleConfig\ModuleConfigDescriptor;
use PHPUnit\Framework\TestCase;

class ConfigFactoryTest extends TestCase
{
    private ModuleConfigDescriptor $descriptor;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->descriptor = TestDescriptor::create();
        $this->tmpDir = sys_get_temp_dir() . '/config_factory_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        $this->clearEnvVars();
    }

    protected function tearDown(): void
    {
        $this->clearEnvVars();
        $this->removeDir($this->tmpDir);
    }

    private function clearEnvVars(): void
    {
        foreach (TestDescriptor::allEnvVars() as $var) {
            putenv($var);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // --- isEnvConfigMode ---

    public function testIsEnvConfigModeReturnsFalseByDefault(): void
    {
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertFalse($factory->isEnvConfigMode());
    }

    public function testIsEnvConfigModeReturnsTrueWhenSet(): void
    {
        putenv('OCE_TEST_MODULE_ENV_CONFIG=1');
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($factory->isEnvConfigMode());
    }

    public function testIsEnvConfigModeReturnsTrueForTrueString(): void
    {
        putenv('OCE_TEST_MODULE_ENV_CONFIG=true');
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($factory->isEnvConfigMode());
    }

    public function testIsEnvConfigModeReturnsFalseForZero(): void
    {
        putenv('OCE_TEST_MODULE_ENV_CONFIG=0');
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertFalse($factory->isEnvConfigMode());
    }

    public function testIsEnvConfigModeReturnsFalseForFalseString(): void
    {
        putenv('OCE_TEST_MODULE_ENV_CONFIG=false');
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertFalse($factory->isEnvConfigMode());
    }

    // --- isFileConfigMode ---

    public function testIsFileConfigModeReturnsFalseWhenNoFiles(): void
    {
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertFalse($factory->isFileConfigMode());
    }

    public function testIsFileConfigModeReturnsTrueWhenConfigFileExists(): void
    {
        $configPath = $this->tmpDir . '/config.yaml';
        file_put_contents($configPath, "enabled: true\n");
        putenv('OCE_TEST_MODULE_CONFIG_FILE=' . $configPath);

        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($factory->isFileConfigMode());
    }

    public function testIsFileConfigModeReturnsTrueWhenSecretsFileExists(): void
    {
        $secretsPath = $this->tmpDir . '/secrets.yaml';
        file_put_contents($secretsPath, "api_secret: x\n");
        putenv('OCE_TEST_MODULE_SECRETS_FILE=' . $secretsPath);

        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($factory->isFileConfigMode());
    }

    // --- isExternalConfigMode ---

    public function testIsExternalConfigModeReturnsFalseByDefault(): void
    {
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertFalse($factory->isExternalConfigMode());
    }

    public function testIsExternalConfigModeReturnsTrueForEnvConfig(): void
    {
        putenv('OCE_TEST_MODULE_ENV_CONFIG=1');
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($factory->isExternalConfigMode());
    }

    public function testIsExternalConfigModeReturnsTrueForFileConfig(): void
    {
        $configPath = $this->tmpDir . '/config.yaml';
        file_put_contents($configPath, "enabled: true\n");
        putenv('OCE_TEST_MODULE_CONFIG_FILE=' . $configPath);

        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertTrue($factory->isExternalConfigMode());
    }

    // --- createConfigAccessor ---

    public function testCreatesGlobalsAccessorByDefault(): void
    {
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertInstanceOf(GlobalsAccessor::class, $factory->createConfigAccessor());
    }

    public function testCreatesEnvironmentConfigAccessorWhenEnvConfigSet(): void
    {
        putenv('OCE_TEST_MODULE_ENV_CONFIG=1');
        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertInstanceOf(EnvironmentConfigAccessor::class, $factory->createConfigAccessor());
    }

    public function testCreatesFileConfigAccessorWhenConfigFileExists(): void
    {
        $configPath = $this->tmpDir . '/config.yaml';
        file_put_contents($configPath, "enabled: true\n");
        putenv('OCE_TEST_MODULE_CONFIG_FILE=' . $configPath);

        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertInstanceOf(FileConfigAccessor::class, $factory->createConfigAccessor());
    }

    public function testFileConfigTakesPrecedenceOverEnvConfig(): void
    {
        $configPath = $this->tmpDir . '/config.yaml';
        file_put_contents($configPath, "enabled: true\n");
        putenv('OCE_TEST_MODULE_CONFIG_FILE=' . $configPath);
        putenv('OCE_TEST_MODULE_ENV_CONFIG=1');

        $factory = new ConfigFactory($this->descriptor, TestDescriptor::emptyGlobalsBag());

        $this->assertInstanceOf(FileConfigAccessor::class, $factory->createConfigAccessor());
    }
}
