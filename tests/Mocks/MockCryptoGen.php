<?php

declare(strict_types=1);

namespace OpenEMR\Common\Crypto;

/**
 * Mock CryptoGen — uses base64 to simulate encryption without real crypto dependencies.
 */
class CryptoGen
{
    public function encryptStandard(string $value): string
    {
        return base64_encode($value);
    }
}
