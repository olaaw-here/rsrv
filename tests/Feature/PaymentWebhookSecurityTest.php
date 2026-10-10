<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regresi untuk celah & kondisi balapan pada webhook Midtrans.
 */
class PaymentWebhookSecurityTest extends TestCase
{
    // RefreshDatabase (bukan DatabaseMigrations): skenario re-booking slot membuat
    // migrate:rollback gagal karena down() migration rebooking-history memasang
    // kembali unique index pada data riwayat yang sah.
    use RefreshDatabase;

    protected const KEY = 'test-server-key';

    protected User $customer;
    protected Resource $resource;
    protected TimeSlot $slot;
    protected Booking $booking;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::KEY]);

        $this->customer = User::factory()->create(['role' => 'customer']);
        $provider = User::factory()->create(['role' => 'provider']);
        $profile = ProviderProfile::factory()->create(['user_id' => $provider->id, 'status' => 'active']);
        $category = Category::factory()->create();

        $this->resource = Resource::factory()->create([
            'provider_id' => $profile->id, 'category_id' => $category->id, 'status' => 'active',
        ]);

        $this->slot = TimeSlot::factory()->create([
            'resource_id' => $this->resource->id,
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'price' => 100000,
        ]);

        $this->booking = Booking::bookSlots($this->customer->id, $this->resource->id, [$this->slot->id]);
        $this->payment = Payment::create([
            'booking_id' => $this->booking->id,
            'gateway' => 'midtrans',
            'transaction_id' => 'TEST-' . Str::upper(Str::random(10)),
            'amount' => 100000,
            'status' => 'pending',
            'expired_at' => $this->booking->expires_at,
        ]);
    }

    protected function notify(string $status, array $extra = [], ?string $key = self::KEY, ?Payment $payment = null): \Illuminate\Testing\TestResponse
    {
        $payload = array_merge([
            'order_id' => ($payment ?? $this->payment)->transaction_id,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => $status,
        ], $extra);

        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $key
        );

        return $this->postJson('/api/webhooks/midtrans', $payload);
    }

    public function test_forged_signature_is_rejected_when_server_key_is_not_configured(): void
    {
        config(['services.midtrans.server_key' => null]);

        // Penyerang menghitung signature dengan kunci kosong — dulu ini LOLOS.
        $this->notify('settlement', ['fraud_status' => 'accept'], key: '')->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $this->payment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'pending_payment']);
    }

    public function test_late_expire_does_not_undo_a_settled_payment(): void
    {
        $this->notify('settlement', ['fraud_status' => 'accept'])->assertOk();
        $this->notify('expire')->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $this->payment->id, 'status' => 'settlement']);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('time_slots', ['id' => $this->slot->id, 'status' => 'booked']);
    }

    public function test_challenge_capture_does_not_confirm_booking(): void
    {
        $this->notify('capture', ['fraud_status' => 'challenge'])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $this->payment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'pending_payment']);
    }

    public function test_capture_flagged_deny_fails_the_payment(): void
    {
        $this->notify('capture', ['fraud_status' => 'deny'])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $this->payment->id, 'status' => 'deny']);
        $this->assertDatabaseHas('time_slots', ['id' => $this->slot->id, 'status' => 'available']);
    }

    public function test_amount_mismatch_is_not_treated_as_paid(): void
    {
        $this->notify('settlement', ['gross_amount' => '1000.00', 'fraud_status' => 'accept'])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $this->payment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'pending_payment']);
    }

    public function test_late_payment_on_expired_booking_reclaims_slot_when_still_free(): void
    {
        $this->booking->update(['expires_at' => now()->subMinute()]);
        $this->artisan('bookings:process-lifecycle')->assertSuccessful();
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'expired']);

        $this->notify('settlement', ['fraud_status' => 'accept'])->assertOk();

        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id, 'status' => 'booked', 'held_by_booking_id' => $this->booking->id,
        ]);
    }

    public function test_late_payment_when_slot_was_taken_flags_refund_instead_of_double_booking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->booking->update(['expires_at' => now()->subMinute()]);
        $this->artisan('bookings:process-lifecycle')->assertSuccessful();

        // Customer lain mengambil slot yang sama.
        $other = User::factory()->create(['role' => 'customer']);
        $winner = Booking::bookSlots($other->id, $this->resource->id, [$this->slot->id]);
        $winner->confirm();

        $this->notify('settlement', ['fraud_status' => 'accept'])->assertOk();

        // Pemenang slot tidak tergeser; pembayaran dicatat lunas & admin diberi tahu untuk refund.
        $this->assertDatabaseHas('bookings', ['id' => $winner->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id, 'status' => 'expired']);
        $this->assertDatabaseHas('payments', ['id' => $this->payment->id, 'status' => 'settlement']);
        $this->assertDatabaseHas('time_slots', ['id' => $this->slot->id, 'held_by_booking_id' => $winner->id]);
        $this->assertTrue(
            Notification::where('user_id', $admin->id)->where('type', 'payment_needs_refund')->exists()
        );
    }
}
