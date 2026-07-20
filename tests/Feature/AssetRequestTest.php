<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetRequestResource\Pages\CreateAssetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_asset_request_page_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($user)->get('/admin/asset-requests/create');

        $response->assertStatus(200);

        Livewire::actingAs($user)
            ->test(CreateAssetRequest::class)
            ->assertSuccessful();
    }
}
