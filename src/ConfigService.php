<?php

declare(strict_types=1);

namespace OpenCoreEMR\ModuleConfig;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Database\QueryUtils;

/**
 * Persist module settings to the OpenEMR globals table via upsert.
 *
 * @author    Michael A. Smith <michael@opencoreemr.com>
 * @copyright Copyright (c) 2026 OpenCoreEMR Inc. <https://www.opencoreemr.com>
 */
class ConfigService
{
    private const UPSERT_SQL = <<<'SQL'
        INSERT INTO `globals` (`gl_name`, `gl_index`, `gl_value`)
        VALUES (?, 0, ?)
        ON DUPLICATE KEY UPDATE `gl_value` = ?
        SQL;

    /**
     * Save a setting to the globals table (plaintext).
     */
    public function saveSetting(string $key, string $value): void
    {
        QueryUtils::sqlStatementThrowException(self::UPSERT_SQL, [$key, $value, $value]);
    }

    /**
     * Encrypt a value with CryptoGen and save it to the globals table.
     */
    public function saveEncryptedSetting(string $key, string $value): void
    {
        $encrypted = (new CryptoGen())->encryptStandard($value);
        $this->saveSetting($key, $encrypted);
    }
}
