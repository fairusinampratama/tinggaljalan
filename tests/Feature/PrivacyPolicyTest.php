<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_renders_localized_seo_for_supported_languages(): void
    {
        $this->seed();

        $expectations = [
            'us' => 'Privacy and Cookie Policy | Tinggal Jalan',
            'id' => 'Kebijakan Privasi dan Cookie | Tinggal Jalan',
            'cn' => '隐私与 Cookie 政策 | Tinggal Jalan',
        ];
        $canonical = rtrim((string) config('app.url'), '/').'/privacy-policy';

        foreach ($expectations as $language => $title) {
            $this->get('/privacy-policy?lang='.$language)
                ->assertOk()
                ->assertSee($title, false)
                ->assertSee($canonical, false);
        }
    }
}
