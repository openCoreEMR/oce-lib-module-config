# oce-lib-module-config

Shared configuration accessor library for OpenCoreEMR modules (`oce-module-*`).

## Package Overview

| Namespace | `OpenCoreEMR\ModuleConfig` |
|-----------|---------------------------|
| Composer name | `opencoreemr/oce-lib-module-config` |
| PHP | >= 8.2, `declare(strict_types=1)` everywhere |
| Style | PSR-12 |
| PHPStan | Level 10 |

## Architecture

### Config accessor stack

`ConfigFactory` determines which accessor to use based on environment:

1. **YAML files exist** → `FileConfigAccessor` (YAML + secret resolution + env overrides)
2. **Env config var set** → `EnvironmentConfigAccessor` (env vars only)
3. **Neither** → `GlobalsAccessor` (wraps OEGlobalsBag / Symfony ParameterBag)

All three implement `ConfigAccessorInterface` (typed getters only: `getString`, `getBoolean`, `getInt`, `has`). No `mixed` getter.

### Key classes

| Class | Role |
|-------|------|
| `ModuleConfigDescriptor` | Value object: YAML key maps, env var names, file paths |
| `ConfigFactory` | Creates the right accessor; accepts descriptor + ParameterBag + optional secret provider |
| `GlobalsAccessor` | Wraps a Symfony `ParameterBag` (e.g. `OEGlobalsBag::getInstance()`) |
| `EnvironmentConfigAccessor` | Reads module config from env vars, delegates system keys to GlobalsAccessor |
| `FileConfigAccessor` | Reads YAML with env var overrides, delegates system keys to GlobalsAccessor |
| `YamlConfigLoader` | Parses YAML with Symfony-style imports and `_secrets` block resolution |
| `SecretProviderInterface` | `getSecret(name, project, version): string` |
| `GcpSecretManagerProvider` | GCSM implementation (optional `suggest` dependency, guarded by `class_exists()`) |
| `NullSecretProvider` | Always throws — for tests or deployments without secret manager |

### Secret resolution flow

```
YAML loaded → imports processed → _secrets block extracted → provider resolves secrets
→ merge into data → env var overrides applied → ParameterBag
```

### Dependencies

- `symfony/http-foundation` and `symfony/yaml` are `require`
- `google/cloud-secret-manager` is `suggest` only — GCSM code never runs unless `_secrets.provider: gcp-secret-manager` is in YAML
- No dependency on `openemr/openemr` — the caller passes `OEGlobalsBag::getInstance()` as a `ParameterBag`

## Commands

```bash
composer check    # php-lint → phpcs → phpstan
composer test     # phpunit
composer fix      # phpcbf
```

## Conventions

- **All PHP files** must have `declare(strict_types=1);`
- **No file-level docblocks** (conflicts with PSR-12 + strict_types). Use class-level docblocks only.
- **No `$GLOBALS` access** — `GlobalsAccessor` wraps a `ParameterBag` passed in from the caller
- **No `mixed` return types** in the public interface — typed getters only
- **One `@phpstan-ignore`** in `GcpSecretManagerProvider` for the optional dependency method call — this is intentional and documented

## Consumers

Modules that use this library:

- `oce-module-sinch-fax` (Phase 2 migration)
- `oce-module-sinch-conversations` (Phase 3 migration)
- `oce-module-onc-registration` (Phase 4)
- `oce-module-oasis` (Phase 4)
