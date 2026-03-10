<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

use OpenCoreEMR\ModuleConfig\Exception\SecretResolutionException;

/**
 * No-op secret provider that always throws.
 *
 * Use in tests or deployments without a secret manager.
 * If a _secrets block is present in YAML but no real provider is configured,
 * this ensures a clear error rather than silent empty values.
 */
class NullSecretProvider implements SecretProviderInterface
{
    public function getSecret(string $secretName, string $project, string $version = 'latest'): string
    {
        throw new SecretResolutionException(
            sprintf(
                'No secret provider configured. Cannot resolve secret "%s" in project "%s". '
                . 'Install google/cloud-secret-manager and configure _secrets.provider in your secrets YAML.',
                $secretName,
                $project
            )
        );
    }
}
