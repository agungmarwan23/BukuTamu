<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Nama Pengguna (Username)');
        $response->assertSee('Kata Sandi');
    }

    public function test_admin_can_authenticate_using_username_and_is_redirected_to_admin(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'admin_test',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'admin_test',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    public function test_resepsionis_can_authenticate_and_is_redirected_to_resepsionis(): void
    {
        $user = User::factory()->resepsionis()->create([
            'username' => 'resepsionis_test',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'resepsionis_test',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/resepsionis');
    }

    public function test_petugas_can_authenticate_and_is_redirected_to_petugas(): void
    {
        $user = User::factory()->petugas()->create([
            'username' => 'petugas_test',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'petugas_test',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/petugas');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'username' => 'user_test',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'user_test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->inactive()->create([
            'username' => 'inactive_user',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/login', [
            'username' => 'inactive_user',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_protected_routes(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/resepsionis')->assertRedirect('/login');
        $this->get('/petugas')->assertRedirect('/login');
    }

    public function test_petugas_cannot_access_admin_route(): void
    {
        $petugas = User::factory()->petugas()->create();

        $response = $this->actingAs($petugas)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_resepsionis_cannot_access_admin_route(): void
    {
        $resepsionis = User::factory()->resepsionis()->create();

        $response = $this->actingAs($resepsionis)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_all_role_dashboards(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertStatus(200);
        $this->actingAs($admin)->get('/resepsionis')->assertStatus(200);
        $this->actingAs($admin)->get('/petugas')->assertStatus(200);
    }
}
