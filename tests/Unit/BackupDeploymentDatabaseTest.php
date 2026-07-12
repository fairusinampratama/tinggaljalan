<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackupDeploymentDatabaseTest extends TestCase
{
    public function test_database_backup_is_blocked_in_testing(): void
    {
        $status = Artisan::call('deploy:backup-database');

        $this->assertSame(1, $status);
        $this->assertStringContainsString('disabled in the testing environment', Artisan::output());
    }
}
