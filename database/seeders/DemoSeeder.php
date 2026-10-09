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
        $m1 = Member::create(['name' => 'Member 1', 'email' => 'member1@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => true,  'is_active' => true, 'locale' => 'nl']);
        $m2 = Member::create(['name' => 'Member 2', 'email' => 'member2@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => true,  'is_active' => true, 'locale' => 'en']);
        $m3 = Member::create(['name' => 'Member 3', 'email' => 'member3@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => false, 'is_active' => true, 'locale' => 'nl']);
        $m4 = Member::create(['name' => 'Member 4', 'email' => 'member4@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => false, 'is_active' => true, 'locale' => 'nl']);
        $m5 = Member::create(['name' => 'Member 5', 'email' => 'member5@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => false, 'is_active' => true, 'locale' => 'en']);
        $m6 = Member::create(['name' => 'Member 6', 'email' => 'member6@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => false, 'is_active' => true, 'locale' => 'en']);
        $m7 = Member::create(['name' => 'Member 7', 'email' => 'member7@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => false, 'is_active' => true, 'locale' => 'en']);
        $m8 = Member::create(['name' => 'Member 8', 'email' => 'member8@beerjudge.local', 'password' => Hash::make('password'), 'is_admin' => false, 'is_active' => true, 'locale' => 'en']);

        $byCode = Style::where('source', 'bjcp_beer')
            ->whereIn('code', [
                '01A', '04A', '04B', '04C', '10A', '10B', '12A', '12C',
                '13A', '13B', '13C', '15A', '15B', '15C', '20B', '21A', '22B', '23A', '25A', '25B',
            ])->get()->keyBy('code');

        $s = fn (string $code): Style => $byCode[$code] ?? Style::first();

        // ---------------------------------------------------------------
        // Competition 1 — Autumn Harvest 2024 (closed, all styles, 5 entries)
        // ---------------------------------------------------------------
        $comp1 = Competition::forceCreate([
            'name' => 'Autumn Harvest 2024',
            'year' => 2024,
            'judging_date' => '2024-10-19',
            'status' => 'closed',
        ]);
        $comp1->styles()->attach(Style::where('source', 'bjcp_beer')->pluck('id'));
        $comp1->judges()->attach([$m1->id, $m2->id, $m4->id]);

        $c1 = [
            Entry::create(['competition_id' => $comp1->id, 'member_id' => $m3->id, 'style_id' => $s('01A')->id, 'bottle_number' => 1]),
            Entry::create(['competition_id' => $comp1->id, 'member_id' => $m5->id, 'style_id' => $s('04A')->id, 'bottle_number' => 2]),
            Entry::create(['competition_id' => $comp1->id, 'member_id' => $m6->id, 'style_id' => $s('13C')->id, 'bottle_number' => 3]),
            Entry::create(['competition_id' => $comp1->id, 'member_id' => $m7->id, 'style_id' => $s('21A')->id, 'bottle_number' => 4]),
            Entry::create(['competition_id' => $comp1->id, 'member_id' => $m8->id, 'style_id' => $s('20B')->id, 'bottle_number' => 5]),
        ];

        // m1 and m2 score all 5; m4 scores the first 3
        $this->sheet($c1[0], $m1, 10, 3, 17, 4, 8, 'Bright citrus hop aroma, slight pine undertone.', 'Clean bitterness, good malt backbone.');
        $this->sheet($c1[0], $m2, 9, 3, 16, 4, 7, 'Tropical fruit, faint resin.', 'Well balanced, slight astringency at finish.');
        $this->sheet($c1[0], $m4, 8, 2, 15, 3, 7, 'Mild citrus, clean.', 'Bitterness a touch high for style.');
        $this->sheet($c1[1], $m1, 8, 3, 15, 4, 7, 'Soft malt, faint honey.', 'Light body, clean finish.');
        $this->sheet($c1[1], $m2, 9, 3, 16, 4, 8, 'Fresh grain, low hop.', 'Crisp and refreshing, true to style.');
        $this->sheet($c1[1], $m4, 7, 2, 14, 3, 6, 'Clean but slightly thin aroma.', 'Lacks a bit of malt depth.');
        $this->sheet($c1[2], $m1, 10, 3, 17, 5, 8, 'Dark chocolate, roast coffee.', 'Full mouthfeel, lingering roast finish.');
        $this->sheet($c1[2], $m2, 11, 3, 18, 5, 9, 'Rich roast, slight molasses.', 'Excellent balance of chocolate and bitterness.');
        $this->sheet($c1[2], $m4, 9, 3, 16, 4, 8, 'Clean roast, faint vanilla.', 'Well made porter, true to form.');
        $this->sheet($c1[3], $m1, 11, 3, 18, 4, 9, 'Intense dry-hop, mango and grapefruit.', 'Juicy and bitter, great balance.');
        $this->sheet($c1[3], $m2, 10, 3, 17, 4, 8, 'Resinous pine, tropical notes.', 'Dank finish, very enjoyable.');
        $this->sheet($c1[4], $m1, 9, 3, 16, 4, 8, 'Roasty, slight chocolate.', 'Dry finish, good carbonation.');
        $this->sheet($c1[4], $m2, 8, 3, 15, 4, 7, 'Mild roast, coffee hints.', 'Slightly thin body for style.');

        // ---------------------------------------------------------------
        // Competition 2 — Spring Cup 2025 (judging, subset of 12 styles, 10 entries)
        // ---------------------------------------------------------------
        $comp2 = Competition::forceCreate([
            'name' => 'Spring Cup 2025',
            'year' => 2025,
            'judging_date' => '2025-04-12',
            'status' => 'judging',
        ]);

        $subsetA = Style::where('source', 'bjcp_beer')->whereIn('code', ['01A', '04A', '04C', '10A', '12C', '13C', '15A', '15B', '21A', '23A', '25A', '25B'])->pluck('id');
        if ($subsetA->isEmpty()) {
            $subsetA = Style::inRandomOrder()->limit(12)->pluck('id');
        }
        $comp2->styles()->attach($subsetA);
        $comp2->judges()->attach([$m1->id, $m2->id, $m5->id, $m6->id]);

        $inA = fn (Style $style): Style => $subsetA->contains($style->id) ? $style : Style::find($subsetA->first());

        $c2 = [
            // 0-2: fully judged
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m3->id, 'style_id' => $inA($s('21A'))->id, 'bottle_number' => 1]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m4->id, 'style_id' => $inA($s('15B'))->id, 'bottle_number' => 2]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m5->id, 'style_id' => $inA($s('25A'))->id, 'bottle_number' => 3]),
            // 3: partially judged (only m1 submitted)
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m6->id, 'style_id' => $inA($s('10A'))->id, 'bottle_number' => 4]),
            // 4-9: untouched
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m7->id, 'style_id' => $inA($s('04A'))->id, 'bottle_number' => 5]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m8->id, 'style_id' => $inA($s('13C'))->id, 'bottle_number' => 6]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m1->id, 'style_id' => $inA($s('23A'))->id, 'bottle_number' => 7]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m2->id, 'style_id' => $inA($s('15A'))->id, 'bottle_number' => 8]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m3->id, 'style_id' => $inA($s('12C'))->id, 'bottle_number' => 9]),
            Entry::create(['competition_id' => $comp2->id, 'member_id' => $m4->id, 'style_id' => $inA($s('25B'))->id, 'bottle_number' => 10]),
        ];

        // Entry 0 (m3 brewed): m1, m2, m5
        $this->sheet($c2[0], $m1, 11, 3, 18, 4, 9, 'Intense citrus and tropical hop aroma.', 'Assertive bitterness, juicy finish.');
        $this->sheet($c2[0], $m2, 10, 3, 17, 4, 8, 'Resinous, grapefruit-forward.', 'Clean fermentation, well attenuated.');
        $this->sheet($c2[0], $m5, 12, 3, 19, 5, 9, 'Excellent dry-hop presence, mango and pine.', 'Outstanding balance, very drinkable for the ABV.');
        // Entry 1 (m4 brewed): m1, m2, m5
        $this->sheet($c2[1], $m1, 10, 3, 17, 4, 8, 'Roasty coffee, faint dark chocolate.', 'Dry, creamy body, well executed.');
        $this->sheet($c2[1], $m2, 9, 3, 16, 4, 7, 'Mild roast, clean.', 'Slightly thin, otherwise true to style.');
        $this->sheet($c2[1], $m5, 11, 3, 18, 5, 9, 'Rich roast aroma, hint of vanilla.', 'Full mouthfeel, long roasty finish.');
        // Entry 2 (m5 brewed): m1, m2, m6 — m5 cannot judge own entry
        $this->sheet($c2[2], $m1, 9, 3, 15, 4, 8, 'Subtle fruity esters, faint spice.', 'Light and effervescent, clean finish.');
        $this->sheet($c2[2], $m2, 8, 3, 14, 3, 7, 'Low hop, mild malt sweetness.', 'Slightly lacking in complexity.');
        $this->sheet($c2[2], $m6, 10, 3, 16, 4, 8, 'Nice Belgian yeast character, pear and honey.', 'Well carbonated, pleasant dry finish.');
        // Entry 3 (m6 brewed): only m1 submitted so far
        $this->sheet($c2[3], $m1, 9, 3, 16, 4, 7, 'Banana and clove, classic hefeweizen profile.', 'Good yeast character, slight phenolic finish.');

        // ---------------------------------------------------------------
        // Competition 3 — Summer Ales 2025 (open, subset of 8 styles, 3 entries)
        // ---------------------------------------------------------------
        $comp3 = Competition::forceCreate([
            'name' => 'Summer Ales 2025',
            'year' => 2025,
            'judging_date' => null,
            'status' => 'open',
        ]);

        $subsetB = Style::where('source', 'bjcp_beer')->whereIn('code', ['04B', '10B', '12A', '13A', '13B', '15C', '20B', '22B'])->pluck('id');
        if ($subsetB->isEmpty()) {
            $subsetB = Style::inRandomOrder()->limit(8)->pluck('id');
        }
        $comp3->styles()->attach($subsetB);

        $inB = fn (Style $style): Style => $subsetB->contains($style->id) ? $style : Style::find($subsetB->first());

        Entry::create(['competition_id' => $comp3->id, 'member_id' => $m4->id, 'style_id' => $inB($s('13A'))->id, 'bottle_number' => 1]);
        Entry::create(['competition_id' => $comp3->id, 'member_id' => $m6->id, 'style_id' => $inB($s('20B'))->id, 'bottle_number' => 2]);
        Entry::create(['competition_id' => $comp3->id, 'member_id' => $m8->id, 'style_id' => $inB($s('04B'))->id, 'bottle_number' => 3]);
    }

    private function sheet(
        Entry $entry,
        Member $judge,
        int $aroma,
        int $appearance,
        int $flavour,
        int $mouthfeel,
        int $overall,
        string $aromaNotes = '',
        string $flavourNotes = '',
    ): void {
        Scoresheet::create([
            'entry_id' => $entry->id,
            'judge_id' => $judge->id,
            'aroma' => $aroma,
            'appearance' => $appearance,
            'flavour' => $flavour,
            'mouthfeel' => $mouthfeel,
            'overall' => $overall,
            'aroma_notes' => $aromaNotes,
            'flavour_notes' => $flavourNotes,
            'client_uuid' => Str::uuid(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }
}
