<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\ProviderProfile;
use App\Models\Refund;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use DatabaseMigrations;

    protected User $customer;
    protected User $admin;
    protected Booking $booking;
    protected Payment $payment;
    protected TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $provider = User::factory()->create(['role' => 'provider']);
        $providerProfile = ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'status' => 'active',
        ]);
        $category = Category::factory()->create();

        $resource = Resource::factory()->create([
            'provider_id' => $providerProfile->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $this->slot = TimeSlot::factory()->create([
            'resource_id' => $resource->id,
            'slot_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'available',
            'price' => 100000,
        ]);

        $this->booking = Booking::bookSlots(
            $this->customer->id,
            $resource->id,
            [$this->slot->id]
        );

        $this->payment = Payment::create([
            'booking_id' => $this->booking->id,
            'gateway' => 'midtrans',
            'transaction_id' => 'TEST-' . Str::upper(Str::random(10)),
            'amount' => 100000,
            'status' => 'pending',
            'expired_at' => $this->booking->expires_at,
        ]);
    }

    public function test_valid_settlement_webhook_confirms_booking_and_marks_payment_settled(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $payload = [
            'order_id' => $this->payment->transaction_id,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . 'test-server-key'
        );

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'settlement',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'booked',
            'held_by_booking_id' => $this->booking->id,
        ]);
        $this->assertDatabaseCount('payment_logs', 1);
    }

    public function test_invalid_webhook_signature_is_rejected_without_changing_payment(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $payload = [
            'order_id' => $this->payment->transaction_id,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => 'invalid-signature',
        ];

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertForbidden();
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'pending_payment',
        ]);
        $this->assertDatabaseCount('payment_logs', 0);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $payload = [
            'order_id' => $this->payment->transaction_id,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . 'test-server-key'
        );

        $this->postJson('/api/webhooks/midtrans', $payload)->assertOk();
        $this->postJson('/api/webhooks/midtrans', $payload)->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'settlement',
        ]);
        $this->assertDatabaseCount('payment_logs', 1);
    }

    public function test_expire_webhook_releases_booking_and_slot(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $payload = [
            'order_id' => $this->payment->transaction_id,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'expire',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . 'test-server-key'
        );

        $response = $this->postJson('/api/webhooks/midtrans', $payload);

        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'expire',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'available',
            'held_by_booking_id' => null,
        ]);
    }

    public function test_admin_can_process_requested_refund_and_financial_state_becomes_refunded(): void
    {
        $this->payment->update(['status' => 'settlement']);
        $refund = Refund::create([
            'payment_id' => $this->payment->id,
            'refund_key' => 'RF-TEST-' . Str::upper(Str::random(8)),
            'amount' => 100000,
            'reason' => 'Customer request',
            'status' => 'requested',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/refunds/{$refund->id}/process");

        $response->assertOk();
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'processed',
            'processed_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'refunded',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'refunded',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'refund_processed',
        ]);
    }

    public function test_provider_cannot_process_refund(): void
    {
        $provider = User::factory()->create(['role' => 'provider']);
        $this->payment->update(['status' => 'settlement']);
        $refund = Refund::create([
            'payment_id' => $this->payment->id,
            'refund_key' => 'RF-TEST-' . Str::upper(Str::random(8)),
            'amount' => 100000,
            'status' => 'requested',
        ]);

        $response = $this->actingAs($provider, 'sanctum')
            ->postJson("/api/admin/refunds/{$refund->id}/process");

        $response->assertForbidden();
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'requested',
        ]);
    }

    public function test_admin_can_reject_requested_refund_without_refunding_payment(): void
    {
        $this->payment->update(['status' => 'settlement']);
        $refund = Refund::create([
            'payment_id' => $this->payment->id,
            'refund_key' => 'RF-TEST-' . Str::upper(Str::random(8)),
            'amount' => 100000,
            'status' => 'requested',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/refunds/{$refund->id}/reject", [
                'reason' => 'Bukti tidak memenuhi ketentuan.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => 'rejected',
            'processed_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'settlement',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $this->booking->id,
            'status' => 'pending_payment',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'refund_rejected',
        ]);
    }
}
