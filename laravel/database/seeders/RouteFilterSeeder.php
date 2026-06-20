<?php

namespace Database\Seeders;

use App\Support\RouteFilterOptions;
use Illuminate\Database\Seeder;

class RouteFilterSeeder extends Seeder
{
    public function run(): void
    {
        RouteFilterOptions::seedDefaults();
    }
}
