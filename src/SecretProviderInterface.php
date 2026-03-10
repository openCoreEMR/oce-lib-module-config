<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

use OpenCoreEMR\ModuleConfig\Exception\SecretResolutionException;

/**
 * Resolve secret values from an external provider (e.g. Google Cloud Secret Manager).
 */
interface SecretProviderInterface
{
    /**
     * Retrieve a secret value
     *
     * @param string $secretName Secret identifier (e.g. Terraform-provisioned secret name)
     * @param string $project    Project identifier (e.g. GCP project ID)
     * @param string $version    Secret version (default: 'latest')
     * @throws SecretResolutionException if the secret cannot be resolved
     */
    public function getSecret(string $secretName, string $project, string $version = 'latest'): string;
}
