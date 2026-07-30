<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShelfIdentityAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shelf_users_table_contains_core_identity_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'core_user_id'));
        $this->assertTrue(Schema::hasColumn('users', 'is_active'));
        $this->assertTrue(Schema::hasColumn('users', 'last_synced_at'));
    }

    public function test_shelf_user_model_accepts_core_identity_fields(): void
    {
        $user = User::factory()->create([
            'core_user_id' => 77,
            'is_active' => true,
            'last_synced_at' => now(),
        ]);

        $this->assertSame(77, $user->core_user_id);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->last_synced_at);
    }

    public function test_shelf_registers_local_shield_role_management(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Route::has('filament.admin.resources.shield.roles.index'));
    }
}
