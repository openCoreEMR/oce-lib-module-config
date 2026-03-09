<?php

/**
 * Interface for secret resolution providers
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
