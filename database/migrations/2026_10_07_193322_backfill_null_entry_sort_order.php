<?php

use App\Models\Entry;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Gives entries without a sort_order one after the competition's current max, in id order.
     * Writes through the query builder so Entry's saving and creating hooks do not fire.
     */
    public function up(): void
    {
        Entry::whereNull('sort_order')
            ->orderBy('id')
            ->get(['id', 'competition_id'])
            ->groupBy('competition_id')
            ->each(function ($entries, $competitionId): void {
                $next = (int) Entry::where('competition_id', $competitionId)->max('sort_order');

                foreach ($entries as $entry) {
                    Entry::whereKey($entry->id)->update(['sort_order' => ++$next]);
                }
            });
    }

    public function down(): void
    {
        // The assigned positions cannot be told apart from admin-set ones.
    }
};
