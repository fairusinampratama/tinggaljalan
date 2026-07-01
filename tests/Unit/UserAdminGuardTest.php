<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserAdminGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_admin_account_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->expectException(ValidationException::class);

        $admin->delete();
    }

    public function test_current_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $this->expectException(ValidationException::class);

        $admin->delete();
    }
}