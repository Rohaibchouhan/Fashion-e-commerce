<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingSeeder::class,
            CategoryBrandSeeder::class,
            ShippingMethodSeeder::class,
            CouponSeeder::class,
            ProductSeeder::class,
            CmsSeeder::class,
        ]);
    }
}
