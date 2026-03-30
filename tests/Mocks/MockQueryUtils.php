<?php

declare(strict_types=1);

namespace OpenEMR\Common\Database;

/**
 * Mock QueryUtils to avoid database calls during tests.
 */
class QueryUtils
{
    /** @var list<array{sql: string, binds: list<mixed>}> */
    private static array $queries = [];

    private static ?\Throwable $nextException = null;

    /**
     * @param list<mixed> $binds
     */
    public static function sqlStatementThrowException(string $sql, array $binds = []): true
    {
        self::$queries[] = ['sql' => $sql, 'binds' => $binds];
        if (self::$nextException !== null) {
            $e = self::$nextException;
            self::$nextException = null;
            throw $e;
        }
        return true;
    }

    public static function setNextException(\Throwable $e): void
    {
        self::$nextException = $e;
    }

    /** @return list<array{sql: string, binds: list<mixed>}> */
    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function reset(): void
    {
        self::$queries = [];
        self::$nextException = null;
    }
}
