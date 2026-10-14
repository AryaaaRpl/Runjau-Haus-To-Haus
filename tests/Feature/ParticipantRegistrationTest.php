<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requirement 12: Admin authentication protection.
     */
    public function test_unauthenticated_user_cannot_access_admin_dashboard_or_participants(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/participants');
        $response->assertRedirect('/login');

        $response = $this->post('/admin/participants', [
            'name' => 'Rio Millen',
            'bib_number' => '420',
        ]);
        $response->assertRedirect('/login');
    }

    /**
     * Requirement 1: Participant registration.
     */
    public function test_admin_can_register_participant(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/participants', [
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);

        $response->assertRedirect(route('admin.participants.index'));
        $this->assertDatabaseHas('participants', [
            'name' => 'RIO MILLEN',
            'bib_number' => '420',
            'rfid_uid' => '0007352925',
        ]);
    }

    /**
     * Requirement 2: Duplicate BIB validation.
     */
    public function test_duplicate_bib_number_is_rejected(): void
    {
        $admin = User::factory()->create();

        Participant::create([
            'name' => 'Runner One',
            'bib_number' => '420',
            'rfid_uid' => '0001111111',
        ]);

        $response = $this->actingAs($admin)->post('/admin/participants', [
            'name' => 'Runner Two',
            'bib_number' => '420', // Duplicate BIB
            'rfid_uid' => '0002222222',
        ]);

        $response->assertSessionHasErrors('bib_number');
        $this->assertEquals(1, Participant::where('bib_number', '420')->count());
    }

    /**
     * Requirement 3: Duplicate UID validation.
     */
    public function test_duplicate_rfid_uid_is_rejected(): void
    {
        $admin = User::factory()->create();

        Participant::create([
            'name' => 'Runner One',
            'bib_number' => '101',
            'rfid_uid' => '0007352925',
        ]);

        $response = $this->actingAs($admin)->post('/admin/participants', [
            'name' => 'Runner Two',
            'bib_number' => '102',
            'rfid_uid' => '0007352925', // Duplicate UID
        ]);

        $response->assertSessionHasErrors('rfid_uid');
        $this->assertEquals(1, Participant::where('rfid_uid', '0007352925')->count());
    }

    /**
     * Requirement 4: Preservation of leading zeros in RFID UIDs and BIBs.
     */
    public function test_preservation_of_leading_zeros_in_rfid_uid(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/participants', [
            'name' => 'RIO MILLEN',
            'bib_number' => '0042',
            'rfid_uid' => '0007352925',
        ]);

        $response->assertSessionHasNoErrors();

        $participant = Participant::where('bib_number', '0042')->first();
        $this->assertNotNull($participant);
        $this->assertSame('0042', $participant->bib_number);
        $this->assertSame('0007352925', $participant->rfid_uid);
    }
}
