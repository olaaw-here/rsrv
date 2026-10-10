<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseMigrations;

    protected User $customer;
    protected User $otherCustomer;
    protected ProviderProfile $providerProfile;
    protected Resource $resource;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->otherCustomer = User::factory()->create(['role' => 'customer']);

        $provider = User::factory()->create(['role' => 'provider']);
        $this->providerProfile = ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'status' => 'active',
            'rating_avg' => 0,
            'total_reviews' => 0,
        ]);

        $category = Category::factory()->create();

        $this->resource = Resource::factory()->create([
            'provider_id' => $this->providerProfile->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        // Slot dibuat untuk BESOK dan 'available', karena Booking::bookSlots()
        // menolak slot yang tanggalnya sudah lewat atau statusnya bukan 'available'.
        $slot = TimeSlot::factory()->create([
            'resource_id' => $this->resource->id,
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'available',
            'price' => 100000,
        ]);

        $this->booking = Booking::bookSlots(
            $this->customer->id,
            $this->resource->id,
            [$slot->id]
        );
        $this->booking->confirm(); // slot jadi 'booked', booking 'confirmed'

        // Simulasikan waktu berjalan: slot sekarang sudah lewat,
        // lalu booking ditandai selesai (review hanya boleh setelah completed).
        $slot->update(['slot_date' => now()->subDay()->format('Y-m-d')]);

        $this->booking->update([
            'status' => 'completed',
            'confirmed_at' => now()->subDays(2),
        ]);
    }

    public function test_customer_can_review_completed_own_booking(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", [
                'rating' => 5,
                'comment' => 'Tempatnya nyaman dan proses booking lancar.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('comment', 'Tempatnya nyaman dan proses booking lancar.');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'resource_id' => $this->resource->id,
            'rating' => 5,
        ]);
    }

    public function test_review_updates_provider_rating_aggregate(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", [
                'rating' => 4,
                'comment' => 'Cukup bagus.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('provider_profiles', [
            'id' => $this->providerProfile->id,
            'rating_avg' => 4.00,
            'total_reviews' => 1,
        ]);
    }

    public function test_customer_cannot_review_booking_that_is_not_completed(): void
    {
        $this->booking->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", [
                'rating' => 5,
            ]);

        // Booking milik sendiri tapi belum selesai -> 422 (bukan 403).
        $response->assertUnprocessable();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_review_another_customers_booking(): void
    {
        $response = $this->actingAs($this->otherCustomer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", [
                'rating' => 5,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_cannot_submit_second_review_for_same_booking(): void
    {
        $payload = [
            'rating' => 5,
            'comment' => 'Review pertama.',
        ];

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", $payload)
            ->assertCreated();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", [
                'rating' => 4,
                'comment' => 'Review kedua.',
            ]);

        // Review ganda -> 409 Conflict.
        $response->assertStatus(409);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_review_validates_rating_range(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/review", [
                'rating' => 6,
                'comment' => 'Rating tidak valid.',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        $this->assertDatabaseCount('reviews', 0);
    }
}