<?php

namespace OpenCoreEMR\ModuleConfig\Tests\Unit;

use OpenCoreEMR\ModuleConfig\Exception\ConfigurationException;
use OpenCoreEMR\ModuleConfig\Exception\SecretResolutionException;
use OpenCoreEMR\ModuleConfig\NullSecretProvider;
use OpenCoreEMR\ModuleConfig\SecretProviderInterface;
use OpenCoreEMR\ModuleConfig\YamlConfigLoader;
use PHPUnit\Framework\TestCase;

class YamlConfigLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/yaml_config_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
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

    private function writeYaml(string $filename, string $content): string
    {
        $path = $this->tmpDir . '/' . $filename;
        file_put_contents($path, $content);
        return $path;
    }

    // --- Basic loading tests ---

    public function testLoadSingleFile(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('config.yaml', "enabled: true\nproject_id: abc123\n");

        $data = $loader->load([$path]);

        $this->assertTrue($data['enabled']);
        $this->assertSame('abc123', $data['project_id']);
    }

    public function testLoadMergesMultipleFiles(): void
    {
        $loader = new YamlConfigLoader();
        $config = $this->writeYaml('config.yaml', "enabled: true\nregion: global\n");
        $secrets = $this->writeYaml('secrets.yaml', "api_secret: secret456\n");

        $data = $loader->load([$config, $secrets]);

        $this->assertTrue($data['enabled']);
        $this->assertSame('global', $data['region']);
        $this->assertSame('secret456', $data['api_secret']);
    }

    public function testLaterFileOverridesEarlier(): void
    {
        $loader = new YamlConfigLoader();
        $first = $this->writeYaml('first.yaml', "region: us\n");
        $second = $this->writeYaml('second.yaml', "region: eu\n");

        $data = $loader->load([$first, $second]);

        $this->assertSame('eu', $data['region']);
    }

    public function testLoadProcessesImports(): void
    {
        $loader = new YamlConfigLoader();
        $this->writeYaml('secrets.yaml', "api_secret: imported_secret\n");
        $config = $this->writeYaml('config.yaml', "imports:\n  - { resource: secrets.yaml }\nenabled: true\n");

        $data = $loader->load([$config]);

        $this->assertTrue($data['enabled']);
        $this->assertSame('imported_secret', $data['api_secret']);
    }

    public function testParentKeysOverrideImportedKeys(): void
    {
        $loader = new YamlConfigLoader();
        $this->writeYaml('base.yaml', "region: from_base\nenabled: false\n");
        $config = $this->writeYaml('config.yaml', "imports:\n  - { resource: base.yaml }\nregion: from_parent\n");

        $data = $loader->load([$config]);

        $this->assertSame('from_parent', $data['region']);
        $this->assertFalse($data['enabled']);
    }

    public function testImportsKeyIsRemovedFromResult(): void
    {
        $loader = new YamlConfigLoader();
        $this->writeYaml('base.yaml', "enabled: true\n");
        $config = $this->writeYaml('config.yaml', "imports:\n  - { resource: base.yaml }\nregion: global\n");

        $data = $loader->load([$config]);

        $this->assertArrayNotHasKey('imports', $data);
    }

    public function testLoadEmptyFileReturnsEmptyArray(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('empty.yaml', '');

        $data = $loader->load([$path]);

        $this->assertSame([], $data);
    }

    public function testLoadWithNoPathsReturnsEmptyArray(): void
    {
        $loader = new YamlConfigLoader();

        $data = $loader->load([]);

        $this->assertSame([], $data);
    }

    public function testImportStringFormat(): void
    {
        $loader = new YamlConfigLoader();
        $this->writeYaml('base.yaml', "enabled: true\n");
        $config = $this->writeYaml('config.yaml', "imports:\n  - base.yaml\nregion: global\n");

        $data = $loader->load([$config]);

        $this->assertTrue($data['enabled']);
        $this->assertSame('global', $data['region']);
    }

    // --- Error handling tests ---

    public function testLoadThrowsOnUnreadableFile(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('unreadable.yaml', "enabled: true\n");
        chmod($path, 0000);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('not readable');

        try {
            $loader->load([$path]);
        } finally {
            chmod($path, 0644);
        }
    }

    public function testLoadThrowsOnMalformedYaml(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('bad.yaml', "enabled: true\n  bad_indent: here\n");

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid YAML');

        $loader->load([$path]);
    }

    public function testLoadThrowsOnNonMappingYaml(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('scalar.yaml', "just a string\n");

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('must contain a YAML mapping');

        $loader->load([$path]);
    }

    // --- hasConfigFiles / resolveFilePaths ---

    public function testHasConfigFilesReturnsTrueWhenFileExists(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('config.yaml', "enabled: true\n");

        $this->assertTrue($loader->hasConfigFiles([$path]));
    }

    public function testHasConfigFilesReturnsFalseWhenNoFilesExist(): void
    {
        $loader = new YamlConfigLoader();

        $this->assertFalse($loader->hasConfigFiles([
            '/nonexistent/config.yaml',
            '/also/nonexistent/secrets.yaml',
        ]));
    }

    public function testHasConfigFilesReturnsTrueIfAnyFileExists(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', "api_secret: x\n");

        $this->assertTrue($loader->hasConfigFiles([
            '/nonexistent/config.yaml',
            $path,
        ]));
    }

    public function testResolveFilePathsFiltersToExisting(): void
    {
        $loader = new YamlConfigLoader();
        $existing = $this->writeYaml('config.yaml', "enabled: true\n");

        $result = $loader->resolveFilePaths([
            '/nonexistent/config.yaml',
            $existing,
            '/also/nonexistent/secrets.yaml',
        ]);

        $this->assertSame([$existing], $result);
    }

    public function testResolveFilePathsReturnsEmptyWhenNoneExist(): void
    {
        $loader = new YamlConfigLoader();

        $result = $loader->resolveFilePaths([
            '/nonexistent/a.yaml',
            '/nonexistent/b.yaml',
        ]);

        $this->assertSame([], $result);
    }

    // --- _secrets block tests ---

    public function testSecretsBlockIsParsedAndResolved(): void
    {
        $mockProvider = $this->createMock(SecretProviderInterface::class);
        $mockProvider->expects($this->once())
            ->method('getSecret')
            ->with('terraform_fax_secret', 'my-gcp-project', 'latest')
            ->willReturn('resolved-secret-value');

        $loader = new YamlConfigLoader($mockProvider);
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: gcp-secret-manager
  project: my-gcp-project
  map:
    api_secret: terraform_fax_secret
webhook_hash: "$2y$10$abc"
YAML);

        $data = $loader->load([$path]);

        $this->assertSame('resolved-secret-value', $data['api_secret']);
        $this->assertSame('$2y$10$abc', $data['webhook_hash']);
        $this->assertArrayNotHasKey('_secrets', $data);
    }

    public function testSecretsBlockResolvesMultipleSecrets(): void
    {
        $mockProvider = $this->createMock(SecretProviderInterface::class);
        $mockProvider->expects($this->exactly(2))
            ->method('getSecret')
            ->willReturnMap([
                ['secret_a', 'proj-1', 'latest', 'value-a'],
                ['secret_b', 'proj-1', 'latest', 'value-b'],
            ]);

        $loader = new YamlConfigLoader($mockProvider);
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: gcp-secret-manager
  project: proj-1
  map:
    key_a: secret_a
    key_b: secret_b
YAML);

        $data = $loader->load([$path]);

        $this->assertSame('value-a', $data['key_a']);
        $this->assertSame('value-b', $data['key_b']);
    }

    public function testNoSecretsBlockPassesThroughUnchanged(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('config.yaml', "enabled: true\nregion: us\n");

        $data = $loader->load([$path]);

        $this->assertTrue($data['enabled']);
        $this->assertSame('us', $data['region']);
        $this->assertArrayNotHasKey('_secrets', $data);
    }

    public function testSecretsBlockOverwritesPlainYamlValue(): void
    {
        $mockProvider = $this->createMock(SecretProviderInterface::class);
        $mockProvider->method('getSecret')->willReturn('from-gcsm');

        $loader = new YamlConfigLoader($mockProvider);
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
api_secret: placeholder
_secrets:
  provider: gcp-secret-manager
  project: proj
  map:
    api_secret: real_secret
YAML);

        $data = $loader->load([$path]);

        $this->assertSame('from-gcsm', $data['api_secret']);
    }

    public function testSecretsBlockThrowsOnMissingProvider(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  project: proj
  map:
    key: secret
YAML);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('_secrets.provider is required');

        $loader->load([$path]);
    }

    public function testSecretsBlockThrowsOnMissingProject(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: gcp-secret-manager
  map:
    key: secret
YAML);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('_secrets.project is required');

        $loader->load([$path]);
    }

    public function testSecretsBlockThrowsOnMissingMap(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: gcp-secret-manager
  project: proj
YAML);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('_secrets.map is required');

        $loader->load([$path]);
    }

    public function testSecretsBlockThrowsOnEmptyMap(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: gcp-secret-manager
  project: proj
  map: {}
YAML);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('_secrets.map is required');

        $loader->load([$path]);
    }

    public function testSecretsBlockThrowsOnUnknownProvider(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: aws-secrets-manager
  project: proj
  map:
    key: secret
YAML);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unknown _secrets.provider');

        $loader->load([$path]);
    }

    public function testSecretsBlockThrowsOnNonMapping(): void
    {
        $loader = new YamlConfigLoader();
        $path = $this->writeYaml('secrets.yaml', "_secrets: not-a-mapping\n");

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('_secrets block must be a YAML mapping');

        $loader->load([$path]);
    }

    public function testNullSecretProviderThrows(): void
    {
        $provider = new NullSecretProvider();

        $this->expectException(SecretResolutionException::class);
        $this->expectExceptionMessage('No secret provider configured');

        $provider->getSecret('my_secret', 'my_project');
    }

    public function testSecretsBlockFromImportedFile(): void
    {
        $mockProvider = $this->createMock(SecretProviderInterface::class);
        $mockProvider->method('getSecret')->willReturn('imported-secret-value');

        $loader = new YamlConfigLoader($mockProvider);
        $this->writeYaml('secrets.yaml', <<<'YAML'
_secrets:
  provider: gcp-secret-manager
  project: proj
  map:
    api_secret: remote_secret
YAML);
        $config = $this->writeYaml('config.yaml', "imports:\n  - { resource: secrets.yaml }\nenabled: true\n");

        $data = $loader->load([$config]);

        $this->assertTrue($data['enabled']);
        $this->assertSame('imported-secret-value', $data['api_secret']);
        $this->assertArrayNotHasKey('_secrets', $data);
    }
}
