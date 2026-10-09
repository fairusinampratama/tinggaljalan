<?php

namespace Tests\Feature;

use Tests\TestCase;

class InlineStrongTest extends TestCase
{
    public function test_cms_emphasis_is_escaped_and_incomplete_markers_are_preserved(): void
    {
        $html = view('partials.inline-strong', ['text' => '**Best For** <script>alert(1)</script> & **unfinished'])->render();

        $this->assertStringContainsString('<strong>Best For</strong>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&amp; **unfinished', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
