<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RuntimeHealthTest extends TestCase
{
    public function test_health_endpoint_reports_the_running_release_revision(): void
    {
        $revisionPath = base_path('REVISION');
        $previousRevision = File::exists($revisionPath) ? File::get($revisionPath) : null;

        File::put($revisionPath, "test-release-sha\n");

        try {
            $response = $this->getJson('/up')
                ->assertOk()
                ->assertExactJson([
                    'status' => 'up',
                    'revision' => 'test-release-sha',
                ]);

            $cacheControl = (string) $response->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cacheControl);
            $this->assertStringContainsString('no-cache', $cacheControl);
            $this->assertStringContainsString('must-revalidate', $cacheControl);
        } finally {
            if ($previousRevision === null) {
                File::delete($revisionPath);
            } else {
                File::put($revisionPath, $previousRevision);
            }
        }
    }

    public function test_health_endpoint_uses_a_null_revision_outside_a_built_release(): void
    {
        $revisionPath = base_path('REVISION');
        $previousRevision = File::exists($revisionPath) ? File::get($revisionPath) : null;

        File::delete($revisionPath);

        try {
            $this->getJson('/up')
                ->assertOk()
                ->assertExactJson([
                    'status' => 'up',
                    'revision' => null,
                ]);
        } finally {
            if ($previousRevision !== null) {
                File::put($revisionPath, $previousRevision);
            }
        }
    }
}
