<?php

namespace Database\Seeders;

use Database\Seeders\Administration\AdminSeeder;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Database\Seeders\Organization\DirectorateSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Database\Seeders\Registry\SearchLocationSeeder;
use Database\Seeders\Registry\TitleTypeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            DirectorateSeeder::class,
            ServiceSeeder::class,
            TitleTypeSeeder::class,
            SearchLocationSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
