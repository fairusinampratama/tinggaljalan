<?php

namespace Tests\Unit;

use App\Models\Faq;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReviewFaqWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_have_no_scope_columns_and_only_three_can_be_active_featured(): void
    {
        $this->assertFalse(Schema::hasColumn('reviews', 'tour_package_id'));
        $this->assertFalse(Schema::hasColumn('reviews', 'destination_id'));

        foreach (range(1, Review::MAX_ACTIVE_FEATURED) as $index) {
            $this->review($index, true, true);
        }

        try {
            $this->review(4, true, true);
            $this->fail('A fourth active featured review was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_featured', $exception->errors());
        }

        $this->assertFalse($this->review(5, true, false)->is_featured);
        $this->assertFalse($this->review(6, false, true)->is_active);
    }

    public function test_faqs_have_no_scope_columns_and_preserve_global_content(): void
    {
        $this->assertFalse(Schema::hasColumn('faqs', 'tour_package_id'));
        $this->assertFalse(Schema::hasColumn('faqs', 'destination_id'));
        $this->assertFalse(Schema::hasColumn('faqs', 'placement'));

        $faq = Faq::create([
            'question' => ['us' => 'Global question'],
            'answer' => ['us' => 'Global answer'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertSame('Global question', $faq->question['us']);
        $this->assertSame('Global answer', $faq->answer['us']);
    }

    private function review(int $index, bool $active, bool $featured): Review
    {
        return Review::create([
            'name' => "Reviewer {$index}",
            'origin' => ['us' => 'Test'],
            'rating' => 5,
            'text' => ['us' => "Review {$index}"],
            'sort_order' => $index,
            'is_active' => $active,
            'is_featured' => $featured,
        ]);
    }

}