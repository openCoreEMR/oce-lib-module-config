<?php

/**
 * Google Cloud Secret Manager provider
 *
 * @package   OpenCoreEMR
 * @link      https://opencoreemr.com
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc
 * @license   GNU General Public License 3
 */

namespace OpenCoreEMR\ModuleConfig;

use OpenCoreEMR\ModuleConfig\Exception\SecretResolutionException;

/**
 * Resolve secrets from Google Cloud Secret Manager.
 *
 * Use Application Default Credentials (Workload Identity in GKE).
 * Cache resolved values in memory for the duration of the request.
 *
 * google/cloud-secret-manager is a suggest (optional) dependency.
 * The class_exists() guard in getClient() ensures a clear error
 * when the library is missing. PHPStan cannot analyze the GCSM
 * method calls without the library installed.
 */
class GcpSecretManagerProvider implements SecretProviderInterface
{
    private const CLIENT_CLASS = 'Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient';

    /** @var array<string, string> Request-scoped cache: "project/secret/version" => value */
    private array $cache = [];

    /** @var object|null Lazily instantiated GCSM client */
    private ?object $client = null;

    public function getSecret(string $secretName, string $project, string $version = 'latest'): string
    {
        $cacheKey = sprintf('%s/%s/%s', $project, $secretName, $version);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $client = $this->getClient();
        $secretVersionName = sprintf(
            'projects/%s/secrets/%s/versions/%s',
            $project,
            $secretName,
            $version
        );

        try {
            // google/cloud-secret-manager is a suggest dependency; types unavailable at analysis time
            /** @phpstan-ignore method.notFound (google/cloud-secret-manager is a suggest dependency) */
            $response = $client->accessSecretVersion($secretVersionName);
            $payload = $response->getPayload();
            $data = $payload->getData();
            if (!is_string($data)) {
                throw new SecretResolutionException(
                    sprintf('Secret "%s" in project "%s" returned non-string payload', $secretName, $project)
                );
            }
        } catch (SecretResolutionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SecretResolutionException(
                sprintf(
                    'Failed to resolve secret "%s" in project "%s": %s',
                    $secretName,
                    $project,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        $this->cache[$cacheKey] = $data;
        return $data;
    }

    private function getClient(): object
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists(self::CLIENT_CLASS)) {
            throw new SecretResolutionException(
                'google/cloud-secret-manager is not installed. '
                . 'Run "composer require google/cloud-secret-manager:^2.1" or remove _secrets.provider from your YAML.'
            );
        }

        /** @var class-string<object> $clientClass */
        $clientClass = self::CLIENT_CLASS;
        $this->client = new $clientClass();
        return $this->client;
    }
}
