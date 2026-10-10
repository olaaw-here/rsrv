<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class BookingTest extends TestCase
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
        $providerProfile = ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'status' => 'active',
        ]);
        $category = Category::factory()->create();

        $this->resource = Resource::factory()->create([
            'provider_id' => $providerProfile->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $this->slot = TimeSlot::factory()->create([
            'resource_id' => $this->resource->id,
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'available',
            'price' => 100000,
        ]);
    }

    public function test_booking_holds_selected_slot_and_calculates_total(): void
    {
        $booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$this->slot->id]
        );

        $this->assertSame('pending_payment', $booking->status);
        $this->assertSame(100000.0, (float) $booking->total_price);

        $this->assertDatabaseHas('booking_slots', [
            'booking_id' => $booking->id,
            'time_slot_id' => $this->slot->id,
            'price_snapshot' => 100000,
        ]);

        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'held',
            'held_by_booking_id' => $booking->id,
        ]);
    }

    public function test_customer_can_view_own_booking(): void
    {
        $booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$this->slot->id]
        );

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/bookings/{$booking->id}");

        $response->assertOk()
            ->assertJsonPath('id', $booking->id)
            ->assertJsonPath('status', 'pending_payment');
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $booking = Booking::bookSlots(
            $owner->id,
            $this->resource->id,
            [$this->slot->id]
        );

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/bookings/{$booking->id}");

        $response->assertForbidden();
    }

    public function test_customer_can_cancel_pending_booking_and_slot_becomes_available_again(): void
    {
        $booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$this->slot->id]
        );

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$booking->id}/cancel", [
                'reason' => 'Jadwal berubah',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('cancellation_reason', 'Jadwal berubah');

        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'available',
            'held_by_booking_id' => null,
            'held_until' => null,
        ]);
    }

    public function test_confirmed_booking_cannot_be_cancelled(): void
    {
        $booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$this->slot->id]
        );
        $booking->confirm();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Booking hanya dapat dibatalkan sebelum pembayaran berhasil.');

        app(BookingService::class)->cancel($booking->fresh(), 'Ingin membatalkan');
    }

    public function test_overdue_pending_booking_is_expired_and_releases_slot(): void
    {
        $booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$this->slot->id]
        );

        $booking->update(['expires_at' => Carbon::now()->subMinute()]);

        $count = app(BookingService::class)->expireOverdueBookings();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'available',
            'held_by_booking_id' => null,
        ]);
    }

    public function test_confirmed_booking_becomes_completed_after_last_slot_ends(): void
    {
        $booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$this->slot->id]
        );
        $booking->confirm();

        $this->slot->update([
            'slot_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        $count = app(BookingService::class)->completeDueBookings();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'booked',
        ]);
    }
}