<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Lowercases stored emails so lookups on a lowercased address find them. An address
     * whose lowercase form already belongs to another member is left alone, so a pair of
     * case-only duplicates cannot fail the deploy; an admin merges those by hand.
     */
    public function up(): void
    {
        DB::table('members')
            ->whereRaw('email <> lower(email)')
            ->whereNotExists(fn ($query) => $query->from('members as other')
                ->whereColumn('other.id', '<>', 'members.id')
                ->whereRaw('lower(other.email) = lower(members.email)'))
            ->update(['email' => DB::raw('lower(email)')]);
    }

    public function down(): void
    {
        // The original capitalisation is not kept.
    }
};
