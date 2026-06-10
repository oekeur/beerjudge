<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Style extends Model
{
    protected $fillable = [
        'code', 'category_name', 'name', 'version',
        'og_min', 'og_max', 'fg_min', 'fg_max',
        'abv_min', 'abv_max', 'ibu_min', 'ibu_max', 'srm_min', 'srm_max',
    ];

    public function competitions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_style');
    }

    public function entries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
