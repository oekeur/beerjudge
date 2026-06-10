<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Member::create([
            'name' => 'Admin',
            'email' => 'admin@beerjudge.local',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
        ]);

        $judge1 = Member::create([
            'name' => 'Alice Judge',
            'email' => 'alice@beerjudge.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $judge2 = Member::create([
            'name' => 'Bob Judge',
            'email' => 'bob@beerjudge.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $ipa = Style::where('code', '21A')->first();
        $helles = Style::where('code', '4A')->first();
        $porter = Style::where('code', '13C')->first();
        $stout = Style::where('code', '20B')->first();

        if (! $ipa) {
            $ipa = Style::first();
            $helles = $ipa;
            $porter = $ipa;
            $stout = $ipa;
        }

        $comp = Competition::create([
            'name' => 'Spring 2025 Cup',
            'year' => 2025,
            'judging_date' => '2025-04-15',
            'status' => 'judging',
        ]);

        $entries = [
            Entry::create(['competition_id' => $comp->id, 'member_id' => $judge1->id, 'style_id' => $ipa->id, 'bottle_number' => 1]),
            Entry::create(['competition_id' => $comp->id, 'member_id' => $judge2->id, 'style_id' => $helles->id, 'bottle_number' => 2]),
            Entry::create(['competition_id' => $comp->id, 'member_id' => $admin->id, 'style_id' => $porter->id, 'bottle_number' => 3]),
            Entry::create(['competition_id' => $comp->id, 'member_id' => $judge1->id, 'style_id' => $stout->id, 'bottle_number' => 4]),
        ];

        Scoresheet::create([
            'entry_id' => $entries[0]->id, 'judge_id' => $admin->id,
            'aroma' => 9, 'appearance' => 3, 'flavour' => 16, 'mouthfeel' => 4, 'overall' => 8,
            'aroma_notes' => 'Nice citrus and pine.', 'flavour_notes' => 'Good bitterness balance.',
            'client_uuid' => Str::uuid(), 'status' => 'submitted',
        ]);
        Scoresheet::create([
            'entry_id' => $entries[1]->id, 'judge_id' => $admin->id,
            'aroma' => 7, 'appearance' => 2, 'flavour' => 14, 'mouthfeel' => 3, 'overall' => 7,
            'aroma_notes' => 'Clean, subtle malt.', 'flavour_notes' => 'Slightly thin.',
            'client_uuid' => Str::uuid(), 'status' => 'submitted',
        ]);

        Scoresheet::create([
            'entry_id' => $entries[0]->id, 'judge_id' => $judge2->id,
            'aroma' => 11, 'appearance' => 3, 'flavour' => 18, 'mouthfeel' => 4, 'overall' => 9,
            'aroma_notes' => 'Excellent dry-hop aroma.',
            'client_uuid' => Str::uuid(), 'status' => 'submitted',
        ]);
        Scoresheet::create([
            'entry_id' => $entries[2]->id, 'judge_id' => $judge2->id,
            'aroma' => 8, 'appearance' => 3, 'flavour' => 15, 'mouthfeel' => 4, 'overall' => 7,
            'client_uuid' => Str::uuid(), 'status' => 'submitted',
        ]);
    }
}
