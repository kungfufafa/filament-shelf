<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_user_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'login' => 'admin@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'email' => 'ga@example.com',
            'username' => 'adminga',
            'password' => bcrypt('password'),
            'role' => 'general_affair',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'login' => 'adminga',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_without_role_cannot_access_panel(): void
    {
        User::factory()->create([
            'email' => 'norole@example.com',
            'username' => 'norole',
            'password' => bcrypt('password'),
            'role' => null,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'login' => 'norole@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }
}
