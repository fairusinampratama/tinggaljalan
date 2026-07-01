<?php

namespace Database\Seeders;

use App\Models\EmailGatewaySetting;
use App\Models\PaymentSetting;
use App\Models\WhatsappGatewaySetting;
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
                'is_admin' => true,
            ],
        );

        PaymentSetting::midtrans();
        EmailGatewaySetting::current();
        WhatsappGatewaySetting::current();

        $this->call([
            DestinationSeeder::class,
            RouteFilterSeeder::class,
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
