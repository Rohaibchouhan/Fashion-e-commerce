<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    protected static bool $dbSafetyVerified = false;

    /**
     * Hook called by RefreshDatabase before any migration or database refresh occurs.
     */
    protected function beforeRefreshingDatabase()
    {
        $this->enforceStrictMysqlSafetyGuard();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->enforceStrictMysqlSafetyGuard();
    }

    /**
     * Strict database safety guard:
     * 1. Aborts testing immediately if connection driver is not MySQL/MariaDB (SQLite strictly prohibited).
     * 2. Aborts testing immediately if selected database is not exactly 'fashion_ecommerce_test'.
     * 3. Prevents any tests or migrations from touching development or production databases.
     * 4. Verifies database connectivity and engine version via SELECT VERSION() and SELECT DATABASE().
     */
    protected function enforceStrictMysqlSafetyGuard(): void
    {
        try {
            $connection = DB::connection();
            $driver = (string) $connection->getDriverName();

            $rows = $connection->select('SELECT DATABASE() AS current_db, VERSION() AS db_version');
            if (empty($rows)) {
                throw new RuntimeException("CRITICAL DATABASE SAFETY VIOLATION: Unable to query MySQL version and database.");
            }

            $currentDb = (string) ($rows[0]->current_db ?? '');

            self::validateDatabaseSafety($driver, $currentDb);

            if (!self::$dbSafetyVerified) {
                self::$dbSafetyVerified = true;
            }
        } catch (RuntimeException $re) {
            throw $re;
        } catch (Throwable $e) {
            throw new RuntimeException(
                "CRITICAL DATABASE BLOCKED: Dedicated MySQL test database 'fashion_ecommerce_test' is unavailable or unreachable on 127.0.0.1:3306. " .
                "Error: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Pure validation logic used for pre-flight assertions and regression testing.
     */
    public static function validateDatabaseSafety(string $driver, string $databaseName): void
    {
        $driverLower = strtolower(trim($driver));

        if ($driverLower !== 'mysql') {
            throw new RuntimeException(
                "CRITICAL DATABASE SAFETY VIOLATION: Tests must run on MySQL/MariaDB only. " .
                "Current driver is '{$driver}'. SQLite is strictly prohibited."
            );
        }

        if (trim($databaseName) !== 'fashion_ecommerce_test') {
            throw new RuntimeException(
                "CRITICAL DATABASE SAFETY VIOLATION: Tests must run ONLY against dedicated test database 'fashion_ecommerce_test'. " .
                "Current active database is '{$databaseName}'. Development ('fashion_ecommerce') and production databases are strictly protected."
            );
        }
    }
}
