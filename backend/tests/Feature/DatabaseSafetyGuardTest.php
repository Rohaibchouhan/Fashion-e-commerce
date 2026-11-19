<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DatabaseSafetyGuardTest extends TestCase
{
    public function test_active_database_is_strictly_mysql_and_fashion_ecommerce_test(): void
    {
        $driver = DB::connection()->getDriverName();
        $this->assertEquals('mysql', strtolower($driver), 'Database driver must be MySQL/MariaDB.');

        $result = DB::select('SELECT DATABASE() AS current_db, VERSION() AS db_version');
        $this->assertNotEmpty($result);

        $currentDb = $result[0]->current_db;
        $dbVersion = $result[0]->db_version;

        $this->assertEquals(
            'fashion_ecommerce_test',
            $currentDb,
            "Tests must run ONLY against dedicated test database 'fashion_ecommerce_test'."
        );
        $this->assertStringContainsString('MariaDB', $dbVersion, 'Connected engine should be MariaDB/MySQL.');
    }

    public function test_safety_guard_logic_aborts_on_sqlite(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite is strictly prohibited');

        TestCase::validateDatabaseSafety('sqlite', 'fashion_ecommerce_test');
    }

    public function test_safety_guard_logic_aborts_on_non_test_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Development ('fashion_ecommerce') and production databases are strictly protected");

        TestCase::validateDatabaseSafety('mysql', 'fashion_ecommerce');
    }

    public function test_safety_guard_passes_on_mysql_test_db(): void
    {
        TestCase::validateDatabaseSafety('mysql', 'fashion_ecommerce_test');
        $this->assertTrue(true);
    }
}
