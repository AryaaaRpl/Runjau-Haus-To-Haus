<?php

namespace Database\Seeders;

use App\Models\LoopRecord;
use App\Models\Participant;
use App\Models\ScanLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['email' => 'admin@runningevent.test'],
            [
                'name' => 'Event Administrator',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Fictional Participants
        $rioUid = config('rfid.sample_uid', '0007352925');

        $participantsData = [
            [
                'name' => 'RIO MILLEN',
                'bib_number' => '420',
                'rfid_uid' => $rioUid,
                'initial_loops' => 0, // Starts at 0 to demonstrate 0 -> 1 on first scan
            ],
            [
                'name' => 'DEWI LESTARI',
                'bib_number' => '101',
                'rfid_uid' => '0008412034',
                'initial_loops' => 3,
            ],
            [
                'name' => 'AHMAD FAUZI',
                'bib_number' => '205',
                'rfid_uid' => '0001928374',
                'initial_loops' => 2,
            ],
            [
                'name' => 'SITI NURHALIZA',
                'bib_number' => '312',
                'rfid_uid' => '0005647382',
                'initial_loops' => 1,
            ],
            [
                'name' => 'BUDI SANTOSO',
                'bib_number' => '108',
                'rfid_uid' => '0009988776',
                'initial_loops' => 4,
            ],
            [
                'name' => 'EKO PRASETYO',
                'bib_number' => '501',
                'rfid_uid' => '0003322114',
                'initial_loops' => 0,
            ],
            [
                'name' => 'NADIA PUTRI',
                'bib_number' => '220',
                'rfid_uid' => null, // Demonstrates participant without registered RFID yet
                'initial_loops' => 0,
            ],
            [
                'name' => 'FARHAN RAMADHAN',
                'bib_number' => '333',
                'rfid_uid' => '0004455667',
                'initial_loops' => 2,
            ],
        ];

        $baseTime = Carbon::now()->subMinutes(30);

        foreach ($participantsData as $data) {
            $initialLoops = $data['initial_loops'];
            unset($data['initial_loops']);

            $participant = Participant::updateOrCreate(
                ['bib_number' => $data['bib_number']],
                $data
            );

            // Generate initial loops if configured
            if ($initialLoops > 0 && $participant->rfid_uid) {
                for ($i = 1; $i <= $initialLoops; $i++) {
                    $loopTime = $baseTime->copy()->addMinutes($i * 6);

                    LoopRecord::updateOrCreate(
                        [
                            'participant_id' => $participant->id,
                            'loop_number' => $i,
                        ],
                        [
                            'rfid_uid' => $participant->rfid_uid,
                            'scanned_at' => $loopTime,
                        ]
                    );

                    ScanLog::create([
                        'rfid_uid' => $participant->rfid_uid,
                        'participant_id' => $participant->id,
                        'status' => 'accepted',
                        'message' => "Putaran ke-{$i} berhasil dicatat.",
                        'resulting_loop_count' => $i,
                        'scanned_at' => $loopTime,
                    ]);
                }
            }
        }

        // Add one sample rejected scan log for demonstration
        ScanLog::create([
            'rfid_uid' => '0009999999',
            'participant_id' => null,
            'status' => 'rejected',
            'message' => 'Kartu RFID tidak terdaftar dalam sistem.',
            'scanned_at' => Carbon::now()->subMinutes(5),
        ]);
    }
}
