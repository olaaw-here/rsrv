<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_open_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/dashboard/summary')->assertForbidden();
    }

    public function test_provider_cannot_open_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'provider']);
        ProviderProfile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/dashboard/summary')->assertForbidden();
    }

    public function test_admin_can_open_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/dashboard/summary')->assertOk();
    }
}
