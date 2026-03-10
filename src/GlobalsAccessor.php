<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Adapt a Symfony ParameterBag (e.g. OEGlobalsBag) to ConfigAccessorInterface.
 *
 * The calling module passes OEGlobalsBag::getInstance() or any other ParameterBag.
 * This class does not access $GLOBALS directly.
 *
 * @internal Use ConfigFactory::createConfigAccessor() instead of instantiating directly
 */
class GlobalsAccessor implements ConfigAccessorInterface
{
    public function __construct(private readonly ParameterBag $bag)
    {
    }

    public function has(string $key): bool
    {
        return $this->bag->has($key);
    }

    public function getString(string $key, string $default = ''): string
    {
        return $this->bag->getString($key, $default);
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        return $this->bag->getBoolean($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return $this->bag->getInt($key, $default);
    }
}
