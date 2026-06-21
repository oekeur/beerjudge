<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        // Clear any existing test data to ensure isolation between test runs
        DB::table('scoresheets')->delete();
        DB::table('entries')->delete();
        DB::table('competition_style')->delete();
        DB::table('competitions')->delete();
        DB::table('styles')->delete();
        DB::table('members')->delete();

        $style = Style::create([
            'source' => 'custom',
            'code' => 'BT1',
            'category_name' => 'Browser Test Category',
            'name' => 'Test Lager',
            'version' => null,
        ]);

        $judge = Member::create([
            'name' => 'Browser Judge',
            'email' => 'judge@browser-test.local',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        $brewer = Member::create([
            'name' => 'Browser Brewer',
            'email' => 'brewer@browser-test.local',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        // Competition in judging status — used by judging screen and judge registration tests
        $judgingComp = Competition::create([
            'name' => 'Browser Test Cup 2025',
            'year' => 2025,
            'judging_date' => now()->toDateString(),
            'status' => 'judging',
        ]);
        $judgingComp->styles()->attach($style->id);

        Entry::create([
            'competition_id' => $judgingComp->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
            'bottle_number' => 1,
        ]);

        // Competition in open status — used by entry submission test
        $openComp = Competition::create([
            'name' => 'Browser Open Cup 2025',
            'year' => 2025,
            'judging_date' => null,
            'status' => 'open',
        ]);
        $openComp->styles()->attach($style->id);
    }
}
