<?php

namespace App\Services;

use App\Models\Competition;
use Illuminate\Support\Facades\DB;

class BottleNumberAssignmentService
{
    public function assign(Competition $competition): void
    {
        DB::transaction(function () use ($competition) {
            $i = 1;
            $competition->entries()->orderBy('id')->each(function ($entry) use (&$i) {
                $entry->update(['bottle_number' => $i++]);
            });
        });
    }
}
