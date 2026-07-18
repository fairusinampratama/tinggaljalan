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

        $midtrans = PaymentSetting::midtrans();
        $midtrans->update([
            'public_key' => env('MIDTRANS_CLIENT_KEY', 'mock-midtrans-client-key'),
            'secret_key' => env('MIDTRANS_SERVER_KEY', 'mock-midtrans-server-key'),
            'mode' => 'sandbox',
        ]);

        $manual = PaymentSetting::firstOrCreate(
            ['gateway' => 'manual'],
            ['public_label' => 'Manual Bank Transfer', 'is_enabled' => false]
        );
        $manual->update([
            'manual_bank_accounts' => [
                [
                    'bank_name' => 'BCA',
                    'account_name' => 'Tinggal Jalan',
                    'account_number' => '1234567890',
                ],
                [
                    'bank_name' => 'Mandiri',
                    'account_name' => 'Tinggal Jalan',
                    'account_number' => '0987654321',
                ]
            ],
        ]);
        $email = EmailGatewaySetting::current();
        $email->update([
            'provider' => 'smtp',
            'is_enabled' => true,
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'username' => 'afe54a001@smtp-brevo.com',
            'password' => env('SMTP_PASSWORD', 'mock-brevo-smtp-key'),
            'from_address' => 'fairusinampratama@gmail.com',
            'from_name' => 'Tinggal Jalan'
        ]);

        $wa = WhatsappGatewaySetting::current();
        $wa->update([
            'provider' => 'whatspie',
            'is_enabled' => true,
            'api_base_url' => 'https://api.whatspie.com/',
            'api_token' => env('WHATSPIE_API_TOKEN', 'mock-whatspie-api-token'),
            'session_id' => '6289688597253'
        ]);

        $this->call([
            DestinationSeeder::class,
            RouteFilterSeeder::class,
            TourPackageSeeder::class,
            NewsSeeder::class,
            FaqSeeder::class,
            HomeContentSeeder::class,
            HeroSlideSeeder::class,
            BookingOptionSeeder::class,
            PlatformLinkSeeder::class,
            SiteSettingSeeder::class,
            ReviewSeeder::class,
            AboutPageSeeder::class,
            TeamMemberSeeder::class,
            CompanyMilestoneSeeder::class,
        ]);
    }
}
