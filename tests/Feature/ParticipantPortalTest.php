<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\RaceRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ParticipantPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeParticipant(): Participant
    {
        return Participant::create(['name' => 'RIO MILLEN', 'bib_number' => '420', 'rfid_uid' => '0007352925']);
    }

    private function gpx(int $points = 200): UploadedFile
    {
        $pts = '';
        for ($i = 0; $i < $points; $i++) {
            $lat = -8.36 + 0.0001 * $i;
            $pts .= "<trkpt lat=\"{$lat}\" lon=\"114.15\"><time>2026-10-12T05:".sprintf('%02d:%02d', intdiv($i, 60), $i % 60).'Z</time></trkpt>';
        }

        return UploadedFile::fake()->createWithContent('pagi.gpx',
            '<?xml version="1.0"?><gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1"><trk><name>Lari pagi</name><trkseg>'.$pts.'</trkseg></trk></gpx>');
    }

    public function test_participant_gets_random_token_on_create(): void
    {
        $p = $this->makeParticipant();

        $this->assertSame(40, strlen($p->access_token));
        $this->assertArrayNotHasKey('access_token', $p->toArray());
    }

    public function test_dashboard_shows_race_distance_from_loops(): void
    {
        Config::set('rfid.debounce_seconds', 0);
        Config::set('rfid.track_length_m', 400);
        $p = $this->makeParticipant();

        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);

        $this->get('/p/'.$p->access_token)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('RIO MILLEN')
            ->assertSee('1,20'); // 3 x 400 m
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->makeParticipant();

        $this->get('/p/salah')->assertNotFound();
        $this->get('/p/'.str_repeat('a', 40))->assertNotFound();
    }

    public function test_rotated_token_invalidates_old_link(): void
    {
        $p = $this->makeParticipant();
        $old = $p->access_token;

        $this->actingAs(User::factory()->create())
            ->post(route('admin.participants.rotate-token', $p))
            ->assertRedirect();

        $this->get('/p/'.$old)->assertNotFound();
        $this->get('/p/'.$p->fresh()->access_token)->assertOk();
    }

    public function test_gpx_upload_creates_run_and_can_be_deleted(): void
    {
        $p = $this->makeParticipant();

        $this->post("/p/{$p->access_token}/runs", ['gpx' => $this->gpx()])
            ->assertRedirect("/p/{$p->access_token}");

        $run = $p->runs()->first();
        $this->assertNotNull($run);
        $this->assertEqualsWithDelta(2213, $run->distance_m, 20); // 199 x ~11.1 m
        $this->assertSame(199, $run->duration_s);

        $this->delete("/p/{$p->access_token}/runs/{$run->id}")->assertRedirect();
        $this->assertSame(0, $p->runs()->count());
    }

    public function test_cannot_delete_another_participants_run(): void
    {
        $a = $this->makeParticipant();
        $b = Participant::create(['name' => 'DEWI', 'bib_number' => '101']);

        $this->post("/p/{$a->access_token}/runs", ['gpx' => $this->gpx()]);
        $run = $a->runs()->first();

        $this->delete("/p/{$b->access_token}/runs/{$run->id}")->assertNotFound();
        $this->assertSame(1, $a->runs()->count());
    }

    public function test_invalid_gpx_is_rejected(): void
    {
        $p = $this->makeParticipant();
        $bad = UploadedFile::fake()->createWithContent('x.gpx', 'bukan xml');

        $this->post("/p/{$p->access_token}/runs", ['gpx' => $bad])->assertSessionHasErrors('gpx');
        $this->assertSame(0, $p->runs()->count());
    }

    public function test_admin_uploaded_race_route_appears_on_participant_dashboard(): void
    {
        $p = $this->makeParticipant();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.race-route.store'), ['route_gpx' => $this->gpx(), 'route_name' => 'Rute Genteng 6K'])
            ->assertRedirect();

        $route = RaceRoute::current();
        $this->assertNotNull($route);
        $this->assertCount(200, $route->route); // rute lomba tidak dipangkas

        auth()->logout();
        $this->get('/p/'.$p->access_token)->assertOk()->assertSee('Rute Genteng 6K')->assertSee('race-map', false);
    }

    public function test_guest_cannot_upload_race_route(): void
    {
        $this->post(route('admin.race-route.store'), ['route_gpx' => $this->gpx()])->assertRedirect(route('login'));
        $this->assertNull(RaceRoute::current());
    }

    public function test_loop_distance_uses_race_route_when_available(): void
    {
        Config::set('rfid.debounce_seconds', 0);
        $p = $this->makeParticipant();
        RaceRoute::create(['name' => 'Rute 6K', 'distance_m' => 6070, 'route' => [[-8.36, 114.15], [-8.35, 114.15]]]);

        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);
        $this->postJson('/api/rfid/scan', ['rfid_uid' => '0007352925']);

        $this->get('/p/'.$p->access_token)->assertOk()->assertSee('12,14'); // 2 x 6,07 km
    }
}
