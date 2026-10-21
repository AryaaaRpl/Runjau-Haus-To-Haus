<?php

namespace Tests\Feature;

use App\Models\LoopRecord;
use App\Models\Participant;
use App\Models\ScanLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RfidScanApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requirement 5: Lookup of a registered card.
     */
    public function test_lookup_of_a_registered_card(): void
    {
        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        $response = $this->getJson('/api/participants/by-card/0007352925');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'participant' => [
                    'id' => $participant->id,
                    'name' => 'RIO MILLEN',
                    'bib_number' => '420',
                    'rfid_uid' => '0007352925',
                    'current_loop_count' => 0,
                ],
            ]);
    }

    /**
     * Requirement 6: Rejection of an unknown card.
     */
    public function test_rejection_of_an_unknown_card(): void
    {
        $response = $this->postJson('/api/rfid/scan', [
            'rfid_uid' => '9999999999',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'status' => 'rejected',
                'participant' => null,
            ]);

        // Verify rejected scan log is created
        $this->assertDatabaseHas('scan_logs', [
            'rfid_uid' => '9999999999',
            'participant_id' => null,
            'status' => 'rejected',
        ]);

        // Verify no loop records were created
        $this->assertEquals(0, LoopRecord::count());
    }

    /**
     * Requirement 7: Successful scan and loop creation.
     */
    public function test_successful_scan_creates_loop_and_increments_count(): void
    {
        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        $response = $this->postJson('/api/rfid/scan', [
            'rfid_uid' => '0007352925',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'accepted',
                'current_loop_count' => 1,
                'loop_number' => 1,
                'participant' => [
                    'name' => 'RIO MILLEN',
                    'bib_number' => '420',
                ],
            ]);

        $this->assertDatabaseHas('loop_records', [
            'participant_id' => $participant->id,
            'rfid_uid' => '0007352925',
            'loop_number' => 1,
        ]);

        $this->assertDatabaseHas('scan_logs', [
            'participant_id' => $participant->id,
            'rfid_uid' => '0007352925',
            'status' => 'accepted',
        ]);
    }

    /**
     * Requirement 8: Correct loop count after multiple accepted scans.
     */
    public function test_correct_loop_count_after_multiple_accepted_scans(): void
    {
        Config::set('rfid.debounce_seconds', 0); // Disable debounce for rapid sequential loops test

        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        // Loop 1
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925'])
            ->assertStatus(200)
            ->assertJson(['current_loop_count' => 1]);

        // Loop 2
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925'])
            ->assertStatus(200)
            ->assertJson(['current_loop_count' => 2]);

        // Loop 3
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925'])
            ->assertStatus(200)
            ->assertJson(['current_loop_count' => 3]);

        $this->assertEquals(3, $participant->fresh()->loop_count);
        $this->assertEquals(3, LoopRecord::where('participant_id', $participant->id)->count());
    }

    /**
     * Requirement 9: Rejected scans do not increase loop count.
     */
    public function test_rejected_scans_do_not_increase_loop_count(): void
    {
        Config::set('rfid.debounce_seconds', 0);

        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        // Valid scan
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925'])->assertStatus(200);

        // Multiple invalid scans with different unknown cards
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0009999991'])->assertStatus(404);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0009999992'])->assertStatus(404);

        // Loop count must remain 1
        $this->assertEquals(1, $participant->fresh()->loop_count);
        $this->assertEquals(1, LoopRecord::count());
        $this->assertEquals(3, ScanLog::count()); // 1 accepted + 2 rejected
    }

    /**
     * Requirement 10: Consistency between loop history and displayed count.
     */
    public function test_consistency_between_loop_history_and_displayed_count(): void
    {
        Config::set('rfid.debounce_seconds', 0);

        $participant = Participant::create([
            'name' => 'DEWI LESTARI',
            'bib_number' => '101',
            'rfid_uid' => '0008412034',
        ]);

        // Record 2 loops
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0008412034']);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0008412034']);

        // Check loops history endpoint
        $response = $this->getJson("/api/participants/{$participant->id}/loops");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'participant' => [
                    'id' => $participant->id,
                    'total_loops' => 2,
                ],
            ]);

        $responseData = $response->json();
        $this->assertCount(2, $responseData['loops']);
        $this->assertEquals(1, $responseData['loops'][0]['loop_number']);
        $this->assertEquals(2, $responseData['loops'][1]['loop_number']);
    }

    /**
     * Requirement 11: Transaction safety and debounce handling.
     */
    public function test_debounce_protects_against_accidental_double_tap(): void
    {
        Config::set('rfid.debounce_seconds', 3);

        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        // First tap: accepted
        $response1 = $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);
        $response1->assertStatus(200)
            ->assertJson([
                'status' => 'accepted',
                'current_loop_count' => 1,
            ]);

        // Immediate second tap (< 3 seconds): ignored duplicate
        $response2 = $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);
        $response2->assertStatus(200)
            ->assertJson([
                'status' => 'ignored_duplicate',
                'current_loop_count' => 1,
            ]);

        // Ensure only 1 loop record was created
        $this->assertEquals(1, LoopRecord::where('participant_id', $participant->id)->count());

        // Ensure scan log recorded the duplicate attempt
        $this->assertDatabaseHas('scan_logs', [
            'participant_id' => $participant->id,
            'status' => 'ignored_duplicate',
        ]);
    }

    /**
     * Requirement 13: Safe handling of invalid input.
     */
    public function test_safe_handling_of_invalid_input(): void
    {
        // Empty string
        $this->postJson('/api/rfid/scan', ['rfid_uid' => ''])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'rejected',
            ]);

        // Whitespace only
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '   '])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'rejected',
            ]);

        // Missing field
        $this->postJson('/api/rfid/scan', [])
            ->assertStatus(422);
    }

    /**
     * Dashboard Summary and Recent Scans API.
     */
    public function test_dashboard_summary_and_recent_scans_endpoints(): void
    {
        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);

        $summaryRes = $this->getJson('/api/dashboard/summary');
        $summaryRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_participants' => 1,
                    'total_loops' => 1,
                    'accepted_scans' => 1,
                ],
            ]);

        $recentRes = $this->getJson('/api/dashboard/recent-scans');
        $recentRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotEmpty($recentRes->json('data'));
    }

    /**
     * Log scan menyimpan jumlah loop pada saat scan, bukan jumlah loop terkini.
     */
    public function test_scan_log_keeps_historical_loop_count(): void
    {
        Config::set('rfid.debounce_seconds', 0);

        Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925'])->assertStatus(200);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925'])->assertStatus(200);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0009999999']);

        $this->assertSame(
            [1, 2],
            ScanLog::where('status', 'accepted')->orderBy('id')->pluck('resulting_loop_count')->all()
        );
        $this->assertNull(ScanLog::where('status', 'rejected')->value('resulting_loop_count'));

        $counts = collect($this->getJson('/api/dashboard/recent-scans')->json('data'))
            ->where('status', 'accepted')
            ->pluck('resulting_loop_count')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2], $counts);
    }

    /**
     * Request yang dikirim ulang dengan client_scan_id sama tidak membuat loop ganda.
     */
    public function test_resent_scan_with_same_client_id_is_not_counted_twice(): void
    {
        Config::set('rfid.debounce_seconds', 0);

        $participant = Participant::create([
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        $payload = ['rfid_uid' => '0007352925', 'client_scan_id' => 'scan-abc-123'];

        $this->postJson('/api/rfid/scan', $payload)->assertStatus(200)->assertJson(['current_loop_count' => 1]);
        $this->postJson('/api/rfid/scan', $payload)->assertStatus(200)->assertJson(['current_loop_count' => 1, 'replayed' => true]);

        $this->assertEquals(1, LoopRecord::where('participant_id', $participant->id)->count());
        $this->assertEquals(1, ScanLog::count());
    }

    /**
     * Endpoint /api/dashboard/participants mengembalikan data peserta terpaginasi.
     */
    public function test_dashboard_participants_endpoint_returns_paginated_data(): void
    {
        Participant::create(['name' => 'Pelari Satu', 'bib_number' => '101', 'rfid_uid' => '000101']);
        Participant::create(['name' => 'Pelari Dua', 'bib_number' => '102', 'rfid_uid' => '000102']);

        $response = $this->getJson('/api/dashboard/participants?per_page=1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 1,
                    'total' => 2,
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Endpoint /api/dashboard/scans mengembalikan data scan terpaginasi.
     */
    public function test_dashboard_scans_endpoint_returns_paginated_data(): void
    {
        $participant = Participant::create(['name' => 'Pelari Scan', 'bib_number' => '303', 'rfid_uid' => '000303']);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '000303']);

        $response = $this->getJson('/api/dashboard/scans?per_page=5');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pagination' => [
                    'current_page' => 1,
                    'total' => 1,
                ],
            ]);

        $this->assertSame('303', $response->json('data.0.bib_number'));
    }

    /**
     * Halaman scan / utama memuat data ringkasan, peserta, dan elemen paginasi.
     */
    public function test_main_scan_page_renders_with_paginated_participants_and_summary(): void
    {
        Participant::create(['name' => 'Budi Santoso', 'bib_number' => '777', 'rfid_uid' => '000777']);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Terminal Reader RFID')
            ->assertSee('Data Peserta')
            ->assertSee('Aktivitas Scan')
            ->assertSee('Budi Santoso')
            ->assertSee('777')
            ->assertSee('pagination-info', false);
    }
}
