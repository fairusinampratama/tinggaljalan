<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('numbers')]
    public function test_phone_numbers_are_normalized(
        string $input,
        string $country,
        string $expected,
        ?string $detectedCountry,
    ): void {
        $this->assertSame($expected, PhoneNumber::normalize($input, $country));
        $this->assertTrue(PhoneNumber::isValid($expected, $country));
        $this->assertSame($detectedCountry, PhoneNumber::detectCountry($expected));
    }

    public static function numbers(): array
    {
        return [
            'indonesian local' => ['0812 3456 7890', 'ID', '+6281234567890', 'ID'],
            'indonesian international' => ['+62 812-3456-7890', 'ID', '+6281234567890', 'ID'],
            'international prefix' => ['0065 8123 4567', 'ID', '+6581234567', 'SG'],
            'united states' => ['415 555 2671', 'US', '+14155552671', 'US'],
            'canada shared calling code' => ['416 555 2671', 'CA', '+14165552671', 'CA'],
            'united kingdom' => ['020 7946 0018', 'GB', '+442079460018', 'GB'],
            'china' => ['138 0013 8000', 'CN', '+8613800138000', 'CN'],
        ];
    }

    public function test_impossible_number_is_invalid_and_not_normalized(): void
    {
        $this->assertFalse(PhoneNumber::isValid('+62123', 'ID'));
        $this->assertSame('', PhoneNumber::normalize('not a phone', 'ID'));
        $this->assertNull(PhoneNumber::detectCountry('not a phone'));
    }
}
