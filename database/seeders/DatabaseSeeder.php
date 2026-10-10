<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Category;
use App\Models\Notification;
use App\Models\OperationalHour;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Refund;
use App\Models\Resource;
use App\Models\ResourceImage;
use App\Models\Review;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder ini membuat akun demo (termasuk admin) dengan password "password".
        // JANGAN pernah dijalankan di production; buat admin lewat
        // `php artisan app:create-admin`.
        if (app()->isProduction()) {
            $this->command?->error('DatabaseSeeder dinonaktifkan di production. Gunakan: php artisan app:create-admin');
            return;
        }

        $password = Hash::make('password');

        // -------------------------------------------------------------
        // Demo accounts
        // -------------------------------------------------------------
        $admin = User::create([
            'name' => 'Admin RSRV',
            'email' => 'admin@rsrv.test',
            'password' => $password,
            'phone' => '081200000001',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $customer = User::create([
            'name' => 'Customer Demo',
            'email' => 'customer@rsrv.test',
            'password' => $password,
            'phone' => '081200000002',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $customerTwo = User::create([
            'name' => 'Customer Kedua',
            'email' => 'customer2@rsrv.test',
            'password' => $password,
            'phone' => '081200000003',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $providerUser = User::create([
            'name' => 'Budi Pratama',
            'email' => 'provider@rsrv.test',
            'password' => $password,
            'phone' => '081200000010',
            'role' => 'provider',
            'email_verified_at' => now(),
        ]);

        $providerTwoUser = User::create([
            'name' => 'Sari Wijaya',
            'email' => 'provider2@rsrv.test',
            'password' => $password,
            'phone' => '081200000011',
            'role' => 'provider',
            'email_verified_at' => now(),
        ]);

        $pendingProviderUser = User::create([
            'name' => 'Provider Pending',
            'email' => 'pending@rsrv.test',
            'password' => $password,
            'phone' => '081200000012',
            'role' => 'provider',
            'email_verified_at' => now(),
        ]);

        // -------------------------------------------------------------
        // Categories
        // -------------------------------------------------------------
        $categories = [];
        foreach ([
            ['name' => 'Olahraga', 'slug' => 'olahraga', 'icon' => '⚽'],
            ['name' => 'Meeting & Event', 'slug' => 'meeting-event', 'icon' => '🏢'],
            ['name' => 'Kreatif', 'slug' => 'kreatif', 'icon' => '🎨'],
            ['name' => 'Konsultasi', 'slug' => 'konsultasi', 'icon' => '💬'],
            ['name' => 'Hiburan', 'slug' => 'hiburan', 'icon' => '🎮'],
        ] as $data) {
            $categories[$data['slug']] = Category::create($data);
        }

        // -------------------------------------------------------------
        // Provider profiles
        // -------------------------------------------------------------
        $provider = ProviderProfile::create([
            'user_id' => $providerUser->id,
            'business_name' => 'Ruang Aktif Surabaya',
            'description' => 'Penyedia lapangan olahraga, ruang meeting, dan venue kecil untuk kebutuhan harian.',
            'address' => 'Jl. Rungkut Madya No. 10',
            'city' => 'Surabaya',
            'latitude' => -7.3226,
            'longitude' => 112.7688,
            'rating_avg' => 4.75,
            'total_reviews' => 2,
            'status' => 'active',
        ]);

        $providerTwo = ProviderProfile::create([
            'user_id' => $providerTwoUser->id,
            'business_name' => 'Kreasi Space Surabaya',
            'description' => 'Creative space untuk foto, workshop, diskusi, dan kegiatan komunitas.',
            'address' => 'Jl. Merr Raya No. 88',
            'city' => 'Surabaya',
            'latitude' => -7.3150,
            'longitude' => 112.8020,
            'rating_avg' => 4.50,
            'total_reviews' => 1,
            'status' => 'active',
        ]);

        $pendingProvider = ProviderProfile::create([
            'user_id' => $pendingProviderUser->id,
            'business_name' => 'Nusantara Sport Center',
            'description' => 'Provider baru yang masih menunggu persetujuan admin.',
            'address' => 'Jl. Dr. Ir. H. Soekarno No. 120',
            'city' => 'Surabaya',
            'latitude' => -7.3000,
            'longitude' => 112.7900,
            'rating_avg' => 0,
            'total_reviews' => 0,
            'status' => 'pending',
        ]);

        // -------------------------------------------------------------
        // Resources
        // -------------------------------------------------------------
        $resources = [];

        $resources['futsal'] = $this->createResource(
            $provider,
            $categories['olahraga'],
            [
                'name' => 'Ruang Aktif Futsal Arena',
                'type' => 'lapangan',
                'description' => 'Lapangan futsal indoor dengan pencahayaan malam dan area tunggu.',
                'capacity' => 14,
                'slot_duration_minutes' => 60,
                'base_price' => 120000,
            ],
            'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=1200&q=80'
        );

        $resources['meeting'] = $this->createResource(
            $provider,
            $categories['meeting-event'],
            [
                'name' => 'Meeting Room Rungkut',
                'type' => 'tempat',
                'description' => 'Ruang meeting berkapasitas kecil dengan meja, kursi, dan area presentasi.',
                'capacity' => 12,
                'slot_duration_minutes' => 60,
                'base_price' => 85000,
            ],
            'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=80'
        );

        $resources['studio'] = $this->createResource(
            $providerTwo,
            $categories['kreatif'],
            [
                'name' => 'Kreasi Photo Studio',
                'type' => 'tempat',
                'description' => 'Studio foto dengan ruang indoor yang cocok untuk portrait, produk, dan konten.',
                'capacity' => 8,
                'slot_duration_minutes' => 60,
                'base_price' => 150000,
            ],
            'https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?auto=format&fit=crop&w=1200&q=80'
        );

        $resources['consultation'] = $this->createResource(
            $providerTwo,
            $categories['konsultasi'],
            [
                'name' => 'Consultation Room Merr',
                'type' => 'konsultasi',
                'description' => 'Ruang privat untuk sesi konsultasi dan diskusi profesional.',
                'capacity' => 4,
                'slot_duration_minutes' => 60,
                'base_price' => 100000,
            ],
            'https://images.unsplash.com/photo-1521791055366-0d553872125f?auto=format&fit=crop&w=1200&q=80'
        );

        // Pending provider has a resource too, but it stays invisible publicly.
        $pendingResource = $this->createResource(
            $pendingProvider,
            $categories['olahraga'],
            [
                'name' => 'Nusantara Badminton Court',
                'type' => 'lapangan',
                'description' => 'Resource milik provider yang masih menunggu approval admin.',
                'capacity' => 8,
                'slot_duration_minutes' => 60,
                'base_price' => 90000,
            ],
            'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80',
            'active'
        );

        // -------------------------------------------------------------
        // Operational hours + future slots
        // -------------------------------------------------------------
        foreach ($resources as $resource) {
            $this->createOperationalHours($resource);
            $this->createSlots($resource, 7);
        }

        // Pending provider can have operational data, but its resource is
        // intentionally excluded from the public catalog by provider status.
        $this->createOperationalHours($pendingResource);
        $this->createSlots($pendingResource, 3);

        // -------------------------------------------------------------
        // Demo bookings / payments for dashboards and customer history.
        // -------------------------------------------------------------
        $pendingSlot = $resources['futsal']->timeSlots()
            ->where('status', 'available')
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->firstOrFail();

        $pendingBooking = $this->createBooking(
            $customer,
            $resources['futsal'],
            $pendingSlot,
            'pending_payment'
        );
        $pendingPayment = Payment::create([
            'booking_id' => $pendingBooking->id,
            'gateway' => 'midtrans',
            'transaction_id' => 'DEMO-' . Str::upper(Str::random(12)),
            'payment_method' => null,
            'amount' => $pendingBooking->total_price,
            'status' => 'pending',
            'expired_at' => now()->addMinutes(15),
        ]);

        $confirmedSlot = $resources['meeting']->timeSlots()
            ->where('status', 'available')
            ->skip(1)
            ->firstOrFail();

        $confirmedBooking = $this->createBooking(
            $customerTwo,
            $resources['meeting'],
            $confirmedSlot,
            'confirmed'
        );
        $confirmedBooking->update(['confirmed_at' => now()->subHours(2)]);
        $confirmedSlot->update([
            'status' => 'booked',
            'held_by_booking_id' => null,
            'held_until' => null,
        ]);

        $confirmedPayment = Payment::create([
            'booking_id' => $confirmedBooking->id,
            'gateway' => 'midtrans',
            'transaction_id' => 'DEMO-' . Str::upper(Str::random(12)),
            'payment_method' => 'bank_transfer',
            'amount' => $confirmedBooking->total_price,
            'status' => 'settlement',
            'paid_at' => now()->subHours(2),
        ]);

        Refund::create([
            'payment_id' => $confirmedPayment->id,
            'refund_key' => 'DEMO-REFUND-' . Str::upper(Str::random(8)),
            'amount' => $confirmedPayment->amount,
            'reason' => 'Demo refund untuk simulasi dashboard admin.',
            'status' => 'requested',
        ]);

        // Completed booking + review to make provider rating and review UI
        // immediately visible in the demo.
        $completedSlot = TimeSlot::create([
            'resource_id' => $resources['studio']->id,
            'slot_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'price' => 150000,
            'status' => 'booked',
        ]);

        $completedBooking = $this->createBooking(
            $customer,
            $resources['studio'],
            $completedSlot,
            'completed'
        );
        $completedBooking->update(['confirmed_at' => now()->subDays(2)]);

        $completedPayment = Payment::create([
            'booking_id' => $completedBooking->id,
            'gateway' => 'midtrans',
            'transaction_id' => 'DEMO-' . Str::upper(Str::random(12)),
            'payment_method' => 'qris',
            'amount' => $completedBooking->total_price,
            'status' => 'settlement',
            'paid_at' => now()->subDays(2),
        ]);

        Review::create([
            'booking_id' => $completedBooking->id,
            'user_id' => $customer->id,
            'resource_id' => $resources['studio']->id,
            'rating' => 5,
            'comment' => 'Studio nyaman dan proses booking mudah.',
        ]);

        $resources['studio']->provider->update([
            'rating_avg' => 5,
            'total_reviews' => 1,
        ]);

        // -------------------------------------------------------------
        // Demo notifications
        // -------------------------------------------------------------
        Notification::create([
            'user_id' => $customer->id,
            'type' => 'booking',
            'title' => 'Booking menunggu pembayaran',
            'message' => 'Booking ' . $pendingBooking->booking_code . ' masih menunggu pembayaran.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $providerUser->id,
            'type' => 'booking',
            'title' => 'Booking baru masuk',
            'message' => 'Ada booking baru untuk Ruang Aktif Futsal Arena.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $admin->id,
            'type' => 'refund',
            'title' => 'Refund menunggu diproses',
            'message' => 'Terdapat refund demo yang menunggu tindakan admin.',
            'is_read' => false,
        ]);
    }

    private function createResource(
        ProviderProfile $provider,
        Category $category,
        array $attributes,
        string $imageUrl,
        string $status = 'active'
    ): Resource {
        $resource = Resource::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'description' => $attributes['description'],
            'capacity' => $attributes['capacity'],
            'slot_duration_minutes' => $attributes['slot_duration_minutes'],
            'base_price' => $attributes['base_price'],
            'status' => $status,
        ]);

        ResourceImage::create([
            'resource_id' => $resource->id,
            'url' => $imageUrl,
            'sort_order' => 0,
        ]);

        return $resource;
    }

    private function createOperationalHours(Resource $resource): void
    {
        for ($day = 0; $day <= 6; $day++) {
            OperationalHour::create([
                'resource_id' => $resource->id,
                'day_of_week' => $day,
                'open_time' => '08:00:00',
                'close_time' => '21:00:00',
                'is_closed' => false,
            ]);
        }
    }

    private function createSlots(Resource $resource, int $days): void
    {
        for ($dayOffset = 1; $dayOffset <= $days; $dayOffset++) {
            $date = now()->addDays($dayOffset)->format('Y-m-d');

            for ($hour = 8; $hour < 20; $hour++) {
                TimeSlot::create([
                    'resource_id' => $resource->id,
                    'slot_date' => $date,
                    'start_time' => sprintf('%02d:00:00', $hour),
                    'end_time' => sprintf('%02d:00:00', $hour + 1),
                    'price' => $resource->base_price,
                    'status' => 'available',
                ]);
            }
        }
    }

    private function createBooking(
        User $customer,
        Resource $resource,
        TimeSlot $slot,
        string $status
    ): Booking {
        $booking = Booking::create([
            'booking_code' => 'BK-DEMO-' . Str::upper(Str::random(6)),
            'user_id' => $customer->id,
            'resource_id' => $resource->id,
            'total_price' => $slot->price,
            'status' => $status,
            'expires_at' => $status === 'pending_payment' ? now()->addMinutes(15) : null,
            'confirmed_at' => $status === 'confirmed' || $status === 'completed' ? now()->subHours(2) : null,
        ]);

        BookingSlot::create([
            'booking_id' => $booking->id,
            'time_slot_id' => $slot->id,
            'price_snapshot' => $slot->price,
        ]);

        if ($status === 'pending_payment') {
            $slot->update([
                'status' => 'held',
                'held_by_booking_id' => $booking->id,
                'held_until' => now()->addMinutes(15),
            ]);
        }

        return $booking;
    }
}
