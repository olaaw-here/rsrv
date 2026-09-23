<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\ProviderProfile;
use App\Models\Resource;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BookingRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    protected Resource $resource;
    protected TimeSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        $provider = User::factory()->create(['role' => 'provider']);
        $providerProfile = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $category = Category::factory()->create();

        $this->resource = Resource::factory()->create([
            'provider_id' => $providerProfile->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $this->slot = TimeSlot::factory()->create([
            'resource_id' => $this->resource->id,
            'status' => 'available',
        ]);
    }

    /**
     * Uji dasar (tanpa concurrency): begitu satu booking berhasil hold slot,
     * percobaan booking berikutnya pada slot yang sama harus ditolak.
     * Ini memvalidasi logic-nya benar, meskipun belum membuktikan
     * keamanannya di bawah request yang BENAR-BENAR bersamaan.
     */
    public function test_second_sequential_booking_attempt_on_same_slot_is_rejected(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Booking::bookSlots($userA->id, $this->resource->id, [$this->slot->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slot sudah dipesan oleh orang lain.');

        Booking::bookSlots($userB->id, $this->resource->id, [$this->slot->id]);
    }

    public function test_slot_status_becomes_held_and_linked_to_the_winning_booking(): void
    {
        $user = User::factory()->create();

        $booking = Booking::bookSlots($user->id, $this->resource->id, [$this->slot->id]);

        $this->assertDatabaseHas('time_slots', [
            'id' => $this->slot->id,
            'status' => 'held',
            'held_by_booking_id' => $booking->id,
        ]);
    }

    /**
     * Uji INTI: benar-benar melepas N request booking secara BERSAMAAN
     * (proses terpisah lewat pcntl_fork, bukan sekadar dipanggil berurutan
     * dalam satu proses PHP) ke slot yang sama, lalu memastikan hanya
     * SATU yang berhasil.
     *
     * SYARAT:
     * - Ekstensi pcntl harus aktif (biasanya tersedia di PHP CLI Linux/macOS,
     *   TIDAK tersedia di Windows / beberapa konfigurasi php-fpm).
     * - Koneksi database TIDAK BOLEH sqlite ':memory:' — child process
     *   punya memory terpisah dari parent sehingga tidak akan melihat data
     *   yang sama. Gunakan MySQL/PostgreSQL sungguhan untuk test ini
     *   (mis. database khusus testing, lihat phpunit.xml).
     * - Test ini TIDAK memakai trait RefreshDatabase (yang membungkus test
     *   dalam satu DB transaction) karena child process perlu melihat data
     *   yang sudah di-COMMIT oleh parent, dan lockForUpdate() butuh row-lock
     *   sungguhan lintas koneksi — keduanya tidak kompatibel dengan
     *   transaction-per-test.
     */
    public function test_only_one_booking_wins_under_true_concurrent_requests(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Ekstensi pcntl tidak tersedia di environment ini.');
        }

        if (config('database.default') === 'sqlite'
            && config('database.connections.sqlite.database') === ':memory:') {
            $this->markTestSkipped('Test concurrency butuh database nyata (MySQL/PostgreSQL), bukan sqlite in-memory.');
        }

        // --- Setup data & COMMIT sungguhan (bukan di dalam transaction test) ---
        $provider = User::factory()->create(['role' => 'provider']);
        $providerProfile = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $category = Category::factory()->create();

        $resource = Resource::factory()->create([
            'provider_id' => $providerProfile->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $slot = TimeSlot::factory()->create([
            'resource_id' => $resource->id,
            'status' => 'available',
        ]);

        $concurrentUsers = User::factory()->count(5)->create();

        $resultFile = tempnam(sys_get_temp_dir(), 'booking_race_');
        file_put_contents($resultFile, ''); // kosongkan

        $childPids = [];

        foreach ($concurrentUsers as $user) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('Gagal fork process untuk simulasi concurrency.');
            }

            if ($pid === 0) {
                // === CHILD PROCESS ===
                // WAJIB reconnect: koneksi DB dari parent ikut ter-duplikasi
                // saat fork dan TIDAK aman dipakai bersamaan oleh 2 proses.
                DB::reconnect();

                $outcome = 'FAIL';

                try {
                    Booking::bookSlots($user->id, $resource->id, [$slot->id]);
                    $outcome = 'SUCCESS';
                } catch (RuntimeException $e) {
                    $outcome = 'FAIL';
                }

                file_put_contents($resultFile, "{$user->id}:{$outcome}\n", FILE_APPEND | LOCK_EX);

                exit(0);
            }

            $childPids[] = $pid;
        }

        // === PARENT PROCESS: tunggu semua child selesai ===
        foreach ($childPids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        // Parent perlu reconnect juga sebelum query verifikasi,
        // karena koneksi lamanya sudah "kotor" akibat proses fork di atas.
        DB::reconnect();

        $lines = array_filter(explode("\n", file_get_contents($resultFile)));
        unlink($resultFile);

        $successCount = collect($lines)
            ->filter(fn ($line) => str_ends_with($line, ':SUCCESS'))
            ->count();

        // --- Asersi utama: tepat SATU booking yang menang ---
        $this->assertSame(
            1,
            $successCount,
            "Diharapkan tepat 1 booking berhasil dari 5 request bersamaan, tapi ada {$successCount}. Ini indikasi double-booking terjadi!"
        );

        // --- Verifikasi state akhir di database ---
        $this->assertSame(1, Booking::where('resource_id', $resource->id)->count());

        $slot->refresh();
        $this->assertSame('held', $slot->status);
        $this->assertNotNull($slot->held_by_booking_id);
    }
}
