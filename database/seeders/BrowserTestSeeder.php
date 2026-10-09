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

        $maxOnlyStyle = Style::create([
            'source' => 'custom',
            'code' => 'BT2',
            'category_name' => 'Browser Test Category',
            'name' => 'SRM Max Only Stout',
            'version' => null,
            'srm_min' => null,
            'srm_max' => 40,
        ]);

        $minOnlyStyle = Style::create([
            'source' => 'custom',
            'code' => 'BT3',
            'category_name' => 'Browser Test Category',
            'name' => 'SRM Min Only Amber',
            'version' => null,
            'srm_min' => 20,
            'srm_max' => null,
        ]);

        $edgeEqualStyle = Style::create([
            'source' => 'custom',
            'code' => 'BT4',
            'category_name' => 'Browser Test Category',
            'name' => 'SRM Edge Equal',
            'version' => null,
            'srm_min' => 40,
            'srm_max' => 40,
        ]);

        // BA guideline fields run to 3000+ characters; the judging sheets must scroll them.
        $longText = implode("\n\n", array_fill(0, 16, 'Long guideline paragraph that wraps over many lines on a phone screen so the bottom sheet has to scroll its content instead of growing past the top of the viewport. Judges read this while scoring.'));

        $baStyle = Style::create([
            'source' => 'ba_beer',
            'code' => 'BA-900',
            'category_name' => 'Browser Test Category',
            'name' => 'BA Test Bitter',
            'version' => '2026',
            'category_description' => $longText,
            'history' => $longText,
            'style_comparison' => $longText,
            'aroma' => $longText,
            'appearance' => "Color: Gold\nClarity: Chill haze\n\n".$longText,
            'srm_min' => 5,
            'srm_max' => 12,
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
        $judgingComp = Competition::forceCreate([
            'name' => 'Browser Test Cup 2025',
            'year' => 2025,
            'judging_date' => now()->toDateString(),
            'status' => 'judging',
        ]);
        $judgingComp->styles()->attach([$style->id, $maxOnlyStyle->id, $minOnlyStyle->id, $edgeEqualStyle->id, $baStyle->id]);

        Entry::create([
            'competition_id' => $judgingComp->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
            'bottle_number' => 1,
        ]);

        // Own data for judging-style-guide-srm.spec.ts: no other spec scores these, so the
        // guideline buttons survive judging-score-buttons.spec.ts submitting entry 1.
        foreach ([[$maxOnlyStyle, 'SRM Max Brewer', 2], [$minOnlyStyle, 'SRM Min Brewer', 3], [$edgeEqualStyle, 'SRM Edge Brewer', 4], [$baStyle, 'BA Brewer', 5]] as [$srmStyle, $brewerName, $bottle]) {
            $srmBrewer = Member::create([
                'name' => $brewerName,
                'email' => strtolower(str_replace(' ', '-', $brewerName)).'@browser-test.local',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_active' => true,
            ]);
            Entry::create([
                'competition_id' => $judgingComp->id,
                'member_id' => $srmBrewer->id,
                'style_id' => $srmStyle->id,
                'bottle_number' => $bottle,
            ]);
        }

        // Competition in open status — used by entry submission test
        $openComp = Competition::forceCreate([
            'name' => 'Browser Open Cup 2025',
            'year' => 2025,
            'judging_date' => null,
            'status' => 'open',
        ]);
        $openComp->styles()->attach($style->id);
    }
}
