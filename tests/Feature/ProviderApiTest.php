<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $providerUser;
    protected Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerUser = User::factory()->create(['role' => 'provider']);
        $profile = ProviderProfile::factory()->create(['user_id' => $this->providerUser->id, 'status' => 'active']);
        $category = Category::factory()->create();

        $this->resource = Resource::factory()->create([
            'provider_id' => $profile->id, 'category_id' => $category->id, 'status' => 'active',
            'slot_duration_minutes' => 60,
        ]);
    }

    public function test_export_route_is_not_swallowed_by_booking_id_route(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => '=HYPERLINK("http://evil","x")']);
        $slot = TimeSlot::factory()->create(['resource_id' => $this->resource->id]);
        Booking::bookSlots($customer->id, $this->resource->id, [$slot->id]);

        $response = $this->actingAs($this->providerUser, 'sanctum')
            ->get('/api/provider/dashboard/bookings/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Booking Code', $csv);
        // Sel berawalan "=" harus dinetralkan agar tidak dieksekusi sebagai formula.
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
    }

    public function test_occupancy_query_works(): void
    {
        TimeSlot::factory()->create([
            'resource_id' => $this->resource->id, 'slot_date' => now()->addDay()->toDateString(), 'status' => 'booked',
        ]);

        $this->actingAs($this->providerUser, 'sanctum')
            ->getJson("/api/provider/resources/{$this->resource->id}/occupancy?from=" . now()->toDateString() . '&to=' . now()->addDays(3)->toDateString())
            ->assertOk()
            ->assertJsonPath('0.slot_terisi', 1);
    }

    public function test_open_day_requires_open_and_close_time(): void
    {
        $hours = collect(range(0, 6))->map(fn ($d) => [
            'day_of_week' => $d, 'is_closed' => $d !== 1, 'open_time' => null, 'close_time' => null,
        ])->all();
        // Hari Senin (1) buka tetapi tanpa jam -> harus ditolak.

        $this->actingAs($this->providerUser, 'sanctum')
            ->putJson("/api/provider/resources/{$this->resource->id}/hours", ['hours' => $hours])
            ->assertUnprocessable();

        $hours[1] = ['day_of_week' => 1, 'is_closed' => false, 'open_time' => '08:00', 'close_time' => '17:00'];

        $this->actingAs($this->providerUser, 'sanctum')
            ->putJson("/api/provider/resources/{$this->resource->id}/hours", ['hours' => $hours])
            ->assertOk();
    }

    public function test_generate_slots_rejects_huge_range_and_skips_past_dates(): void
    {
        $this->resource->operationalHours()->createMany(collect(range(0, 6))->map(fn ($d) => [
            'day_of_week' => $d, 'open_time' => '08:00:00', 'close_time' => '10:00:00', 'is_closed' => false,
        ])->all());

        $this->actingAs($this->providerUser, 'sanctum')
            ->postJson("/api/provider/resources/{$this->resource->id}/slots/generate", [
                'from' => now()->toDateString(), 'to' => now()->addYears(5)->toDateString(),
            ])->assertUnprocessable();

        $this->actingAs($this->providerUser, 'sanctum')
            ->postJson("/api/provider/resources/{$this->resource->id}/slots/generate", [
                'from' => now()->subDays(3)->toDateString(), 'to' => now()->addDay()->toDateString(),
            ])->assertOk();

        $this->assertSame(0, TimeSlot::where('resource_id', $this->resource->id)
            ->where('slot_date', '<', now()->toDateString())->count());
        $this->assertGreaterThan(0, TimeSlot::where('resource_id', $this->resource->id)->count());
    }
}
