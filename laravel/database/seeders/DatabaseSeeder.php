<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tinggaljalan.test'],
            [
                'name' => 'Tinggal Jalan Admin',
                'password' => Hash::make('password'),
            ],
        );

        $this->call([
            DestinationSeeder::class,
            TourPackageSeeder::class,
            NewsSeeder::class,
            FaqSeeder::class,
            HomeContentSeeder::class,
            BookingOptionSeeder::class,
            PlatformLinkSeeder::class,
            SiteSettingSeeder::class,
        ]);
    }
}
