<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

use OpenCoreEMR\ModuleConfig\Exception\ConfigurationException;
use OpenCoreEMR\ModuleConfig\Exception\SecretResolutionException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parse YAML config files, process imports, resolve secrets, and merge into a flat array.
 *
 * Supports Symfony-style `imports` for splitting config across files:
 *
 *     imports:
 *       - { resource: secrets.yaml }
 *
 * Supports a `_secrets` block for resolving values from an external secret provider:
 *
 *     _secrets:
 *       provider: gcp-secret-manager
 *       project: my-gcp-project
 *       map:
 *         api_secret: terraform_secret_name
 *
 * Imported files resolve relative to the importing file's directory.
 * Parent file keys override imported file keys (later overrides earlier).
 */
class YamlConfigLoader
{
    private const SECRETS_KEY = '_secrets';
    private const PROVIDER_GCP = 'gcp-secret-manager';

    public function __construct(private readonly ?SecretProviderInterface $secretProvider = null)
    {
    }

    /**
     * Load and merge multiple YAML config files
     *
     * Later files override earlier files. Each file's own keys override
     * keys from its imports. After merging, resolve any _secrets block.
     *
     * @param list<string> $filePaths absolute paths to YAML files
     * @return array<string, mixed> merged configuration with secrets resolved
     * @throws ConfigurationException if a file is not readable or contains invalid YAML
     * @throws SecretResolutionException if secret resolution fails
     */
    public function load(array $filePaths): array
    {
        $merged = [];
        $visited = [];
        foreach ($filePaths as $filePath) {
            $fileData = $this->loadFile($filePath, $visited);
            $merged = array_merge($merged, $fileData);
        }

        return $this->resolveSecrets($merged);
    }

    /**
     * Check if any config files exist at conventional or overridden paths
     *
     * @param list<string> $paths paths to check
     */
    public function hasConfigFiles(array $paths): bool
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Filter a list of paths to only those that exist
     *
     * @param list<string> $paths candidate paths
     * @return list<string> existing paths
     */
    public function resolveFilePaths(array $paths): array
    {
        $existing = [];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $existing[] = $path;
            }
        }
        return $existing;
    }

    /**
     * Extract and resolve the _secrets block, merging resolved values into data
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws SecretResolutionException if secret resolution fails
     * @throws ConfigurationException if _secrets block is malformed
     */
    private function resolveSecrets(array $data): array
    {
        if (!isset($data[self::SECRETS_KEY])) {
            return $data;
        }

        $secretsBlock = $data[self::SECRETS_KEY];
        unset($data[self::SECRETS_KEY]);

        if (!is_array($secretsBlock)) {
            throw new ConfigurationException(
                sprintf('_secrets block must be a YAML mapping, got %s', get_debug_type($secretsBlock))
            );
        }

        $provider = $secretsBlock['provider'] ?? null;
        if (!is_string($provider) || $provider === '') {
            throw new ConfigurationException('_secrets.provider is required and must be a non-empty string');
        }

        $project = $secretsBlock['project'] ?? null;
        if (!is_string($project) || $project === '') {
            throw new ConfigurationException('_secrets.project is required and must be a non-empty string');
        }

        $map = $secretsBlock['map'] ?? null;
        if (!is_array($map) || $map === []) {
            throw new ConfigurationException('_secrets.map is required and must be a non-empty mapping');
        }

        $secretProvider = $this->getSecretProvider($provider);

        foreach ($map as $yamlKey => $secretName) {
            if (!is_string($yamlKey) || !is_string($secretName)) {
                throw new ConfigurationException(sprintf(
                    '_secrets.map entries must be string => string, got %s => %s',
                    get_debug_type($yamlKey),
                    get_debug_type($secretName)
                ));
            }
            $data[$yamlKey] = $secretProvider->getSecret($secretName, $project);
        }

        return $data;
    }

    /**
     * Get the appropriate secret provider for a provider name
     *
     * @throws ConfigurationException if the provider is unknown
     */
    private function getSecretProvider(string $provider): SecretProviderInterface
    {
        // Allow injected provider (for testing or custom providers)
        if ($this->secretProvider instanceof \OpenCoreEMR\ModuleConfig\SecretProviderInterface) {
            return $this->secretProvider;
        }

        return match ($provider) {
            self::PROVIDER_GCP => new GcpSecretManagerProvider(),
            default => throw new ConfigurationException(
                sprintf('Unknown _secrets.provider "%s". Supported: %s', $provider, self::PROVIDER_GCP)
            ),
        };
    }

    /**
     * Load a single YAML file, processing any imports
     *
     * @param array<string, true> $visited Real paths already loaded (cycle detection)
     * @return array<string, mixed>
     * @throws ConfigurationException if file is not readable, contains invalid YAML, or creates an import cycle
     */
    private function loadFile(string $filePath, array &$visited = []): array
    {
        if (!is_readable($filePath)) {
            throw new ConfigurationException(
                sprintf('Configuration file is not readable: %s', $filePath)
            );
        }

        $realPath = realpath($filePath);
        if ($realPath === false) {
            $realPath = $filePath;
        }

        if (isset($visited[$realPath])) {
            throw new ConfigurationException(
                sprintf('Circular import detected: %s', $filePath)
            );
        }
        $visited[$realPath] = true;

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new ConfigurationException(
                sprintf('Failed to read configuration file: %s', $filePath)
            );
        }

        try {
            $data = Yaml::parse($contents);
        } catch (ParseException $e) {
            throw new ConfigurationException(
                sprintf('Invalid YAML in configuration file %s: %s', $filePath, $e->getMessage()),
                0,
                $e
            );
        }

        if ($data === null) {
            return [];
        }

        if (!is_array($data)) {
            throw new ConfigurationException(
                sprintf('Configuration file must contain a YAML mapping, got %s: %s', get_debug_type($data), $filePath)
            );
        }

        // Process imports
        $importedData = [];
        if (isset($data['imports']) && is_array($data['imports'])) {
            $baseDir = dirname($filePath);
            foreach ($data['imports'] as $import) {
                $resource = is_array($import) ? ($import['resource'] ?? null) : $import;
                if ($resource === null || !is_string($resource)) {
                    continue;
                }
                $importPath = $baseDir . DIRECTORY_SEPARATOR . $resource;
                $importedData = array_merge($importedData, $this->loadFile($importPath, $visited));
            }
            unset($data['imports']);
        }

        // Parent file keys override imported keys
        /** @var array<string, mixed> */
        return array_merge($importedData, $data);
    }
}
