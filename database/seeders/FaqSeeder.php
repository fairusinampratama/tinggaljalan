<?php

namespace Database\Seeders;

use App\Models\Faq;
use Database\Seeders\Concerns\LoadsPrototypeData;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    use LoadsPrototypeData;

    public function run(): void
    {
        foreach ($this->prototypeData()['faqItems'] as $index => $faq) {
            Faq::updateOrCreate(
                ['sort_order' => $index + 1],
                [
                    'question' => $this->localized($faq['question']),
                    'answer' => $this->localized($faq['answer']),
                    'is_active' => true,
                ],
            );
        }
    }
}