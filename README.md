# oce-lib-module-config

Shared configuration accessor library for OpenCoreEMR modules.

Extracts the duplicated config accessor pattern from OCE modules into a single Composer package. Each module provides a `ModuleConfigDescriptor` instead of copy-pasting six config classes.

## Installation

```bash
composer require opencoreemr/oce-lib-module-config
```

## Usage

### 1. Define a module descriptor

```php
use OpenCoreEMR\ModuleConfig\ModuleConfigDescriptor;

$descriptor = new ModuleConfigDescriptor(
    yamlKeyMap: [
        'enabled' => 'oce_sinch_fax_enabled',
        'api_secret' => 'oce_sinch_fax_api_secret',
        // short YAML key => internal config key
    ],
    envOverrideMap: [
        'oce_sinch_fax_enabled' => 'OCE_SINCH_FAX_ENABLED',
        'oce_sinch_fax_api_secret' => 'OCE_SINCH_FAX_API_SECRET',
        // internal config key => env var name
    ],
    envConfigVar: 'OCE_SINCH_FAX_ENV_CONFIG',
    conventionalConfigPath: '/etc/oce/sinch-fax/config.yaml',
    conventionalSecretsPath: '/etc/oce/sinch-fax/secrets.yaml',
    configFileEnvVar: 'OCE_SINCH_FAX_CONFIG_FILE',
    secretsFileEnvVar: 'OCE_SINCH_FAX_SECRETS_FILE',
);
```

### 2. Create a config accessor

```php
use OpenCoreEMR\ModuleConfig\ConfigFactory;
use OpenEMR\Core\OEGlobalsBag;

$factory = new ConfigFactory($descriptor, OEGlobalsBag::getInstance());
$config = $factory->createConfigAccessor();

$apiSecret = $config->getString('oce_sinch_fax_api_secret');
$enabled = $config->getBoolean('oce_sinch_fax_enabled');
$retryCount = $config->getInt('oce_sinch_fax_retry_count', 3);
```

The factory returns the right accessor based on what's available:

1. **YAML files exist** → `FileConfigAccessor` (reads YAML, resolves secrets, applies env overrides)
2. **Env config var set** → `EnvironmentConfigAccessor` (reads from env vars only)
3. **Neither** → `GlobalsAccessor` (reads from OEGlobalsBag / OpenEMR database settings)

### 3. YAML config with secret resolution (optional)

For secrets stored in Google Cloud Secret Manager, use a `_secrets` block in your secrets YAML:

```yaml
# /etc/oce/sinch-fax/secrets.yaml
_secrets:
  provider: gcp-secret-manager
  project: my-tenant-project-id
  map:
    api_secret: fax_api_secret    # Terraform-provisioned secret name

# Non-secret values coexist as plain YAML
webhook_password_bcrypt_hash: "$2y$10$..."
```

Resolution order:

```
YAML loaded → imports processed → _secrets resolved → env var overrides applied
```

Env var overrides always win. The `google/cloud-secret-manager` library is a `suggest` dependency — it's only needed if you use `_secrets.provider: gcp-secret-manager`.

## ConfigAccessorInterface

All accessors implement four typed methods:

```php
interface ConfigAccessorInterface
{
    public function getString(string $key, string $default = ''): string;
    public function getBoolean(string $key, bool $default = false): bool;
    public function getInt(string $key, int $default = 0): int;
    public function has(string $key): bool;
}
```

No `mixed` getter — consumers must use typed accessors.

## Development

```bash
composer install
composer check    # php-lint, phpcs, phpstan
composer test     # phpunit
```

## License

GPL-3.0-or-later
