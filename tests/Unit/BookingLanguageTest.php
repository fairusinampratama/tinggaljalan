<?php

namespace Tests\Unit;

use App\Support\BookingLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingLanguageTest extends TestCase
{
    #[DataProvider('languages')]
    public function test_supported_languages_have_stable_locales_and_labels(string $language, string $locale, string $label): void
    {
        $this->assertSame($language, BookingLanguage::normalize($language));
        $this->assertSame($locale, BookingLanguage::locale($language));
        $this->assertSame($label, BookingLanguage::label($language));
    }

    public function test_unknown_or_missing_language_falls_back_to_english(): void
    {
        $this->assertSame('us', BookingLanguage::normalize(null));
        $this->assertSame('us', BookingLanguage::normalize('fr'));
        $this->assertSame('en', BookingLanguage::locale('fr'));
        $this->assertSame('Payment received', BookingLanguage::translate('booking.payment_received', [], 'fr'));
    }

    public function test_paid_customer_wording_uses_booking_secured_in_all_languages(): void
    {
        $this->assertSame('Booking secured', BookingLanguage::translate('booking.payment_page.trip_confirmed', [], 'us'));
        $this->assertSame('Booking diamankan', BookingLanguage::translate('booking.payment_page.trip_confirmed', [], 'id'));
        $this->assertSame('预订已锁定', BookingLanguage::translate('booking.payment_page.trip_confirmed', [], 'cn'));
        $this->assertSame('正在自动检查付款状态…', BookingLanguage::translate('booking.payment_page.checking', [], 'cn'));
    }
    public static function languages(): array
    {
        return [
            'Indonesian' => ['id', 'id', 'Bahasa Indonesia'],
            'English' => ['us', 'en', 'English'],
            'Simplified Chinese' => ['cn', 'zh_CN', 'Simplified Chinese'],
        ];
    }
}