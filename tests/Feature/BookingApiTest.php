<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Regresi untuk bug alur booking lewat HTTP API (POST /bookings, catatan,
 * pembatalan) yang sebelumnya tidak punya test sama sekali.
 */
class BookingApiTest extends TestCase
{
    use DatabaseMigrations;

    protected User $customer;
    protected Resource $resource;
    protected TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $provider = User::factory()->create(['role' => 'provider']);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'status' => 'active']);
        $category = Category::factory()->create();

        $this->resource = Resource::factory()->create([
            'provider_id' => $profile->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $this->slot = TimeSlot::factory()->create([
            'resource_id' => $this->resource->id,
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'price' => 100000,
        ]);
    }

    /** PaymentService palsu: membuat record Payment tanpa menghubungi Midtrans. */
    protected function fakeGateway(): void
    {
        $fake = Mockery::mock(PaymentService::class);
        $fake->shouldReceive('initiateForBooking')->andReturnUsing(
            fn (Booking $booking) => Payment::create([
                'booking_id' => $booking->id,
                'gateway' => 'midtrans',
                'transaction_id' => 'FAKE-' . Str::upper(Str::random(10)),
                'amount' => $booking->total_price,
                'status' => 'pending',
                'snap_token' => 'snap-token-123',
                'expired_at' => $booking->expires_at,
            ])
        );
        $fake->shouldReceive('cancelGatewayTransaction')->andReturnNull();
        $this->instance(PaymentService::class, $fake);
    }

    public function test_customer_can_create_booking_and_gets_snap_token(): void
    {
        $this->fakeGateway();

        $response = $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'time_slot_ids' => [$this->slot->id],
            'customer_notes' => 'Mohon siapkan proyektor',
        ]);

        $response->assertCreated()
            ->assertJsonPath('snap_token', 'snap-token-123')
            ->assertJsonPath('booking.status', 'pending_payment')
            ->assertJsonPath('booking.customer_notes', 'Mohon siapkan proyektor');

        $this->assertDatabaseHas('time_slots', ['id' => $this->slot->id, 'status' => 'held']);
    }

    public function test_gateway_failure_returns_502_and_releases_the_slot(): void
    {
        $fake = Mockery::mock(PaymentService::class);
        $fake->shouldReceive('initiateForBooking')->andThrow(new RuntimeException('Midtrans down'));
        $this->instance(PaymentService::class, $fake);

        $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'time_slot_ids' => [$this->slot->id],
        ])->assertStatus(502);

        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'available',
            'held_by_booking_id' => null,
        ]);
    }

    public function test_creating_booking_without_midtrans_key_fails_cleanly_and_releases_slot(): void
    {
        config(['services.midtrans.server_key' => null]);

        $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'time_slot_ids' => [$this->slot->id],
        ])->assertStatus(502);

        $this->assertDatabaseHas('time_slots', ['id' => $this->slot->id, 'status' => 'available']);
    }

    public function test_duplicate_slot_ids_are_rejected_by_validation(): void
    {
        $this->actingAs($this->customer, 'sanctum')->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'time_slot_ids' => [$this->slot->id, $this->slot->id],
        ])->assertUnprocessable();
    }

    public function test_customer_can_update_notes_on_own_active_booking(): void
    {
        $booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);

        $this->actingAs($this->customer, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/notes", ['customer_notes' => 'Datang lebih awal'])
            ->assertOk()
            ->assertJsonPath('customer_notes', 'Datang lebih awal');
    }

    public function test_customer_cannot_update_notes_of_another_customers_booking(): void
    {
        $booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/bookings/{$booking->id}/notes", ['customer_notes' => 'x'])
            ->assertForbidden();
    }

    public function test_cancel_requires_reason_and_returns_fresh_status(): void
    {
        $this->fakeGateway();
        $booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);
        Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'midtrans', 'transaction_id' => 'T-' . Str::random(8),
            'amount' => 100000, 'status' => 'pending',
        ]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertUnprocessable();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/cancel", ['reason' => 'Berubah pikiran'])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        // Payment yang masih pending ikut ditutup, dan membatalkan dua kali ditolak.
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'status' => 'cancel']);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/cancel", ['reason' => 'lagi'])
            ->assertStatus(409);
    }

    public function test_pay_endpoint_refuses_expired_booking(): void
    {
        $booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);
        $booking->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/pay")
            ->assertUnprocessable();
    }

    public function test_confirm_is_refused_when_slot_was_already_released(): void
    {
        $booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);
        // Simulasi: hold dilepas oleh pembersihan lazy, slot lalu diambil orang lain.
        $this->slot->update(['status' => 'available', 'held_by_booking_id' => null, 'held_until' => null]);

        $this->expectException(RuntimeException::class);
        $booking->fresh()->confirm();
    }

    public function test_expire_job_also_expires_pending_payments(): void
    {
        $booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);
        $payment = Payment::create([
            'booking_id' => $booking->id, 'gateway' => 'midtrans', 'transaction_id' => 'T-' . Str::random(8),
            'amount' => 100000, 'status' => 'pending',
        ]);
        $booking->update(['expires_at' => now()->subMinute()]);

        $this->artisan('bookings:process-lifecycle')->assertSuccessful();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'expired']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'expire']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'salah'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'salah'])
            ->assertStatus(429);
    }

    public function test_per_page_is_capped(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/bookings?per_page=100000')
            ->assertOk();

        $this->assertSame(100, (new class extends \App\Http\Controllers\Controller {
            public function run(\Illuminate\Http\Request $r): int { return $this->perPage($r); }
        })->run(\Illuminate\Http\Request::create('/x', 'GET', ['per_page' => 100000])));
    }
}
