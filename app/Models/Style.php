<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Style extends Model
{
    protected $fillable = [
        'code', 'category_name', 'name', 'version',
        'overall_impression', 'aroma', 'appearance', 'flavor', 'mouthfeel',
        'comments', 'history', 'style_comparison', 'ingredients', 'examples', 'tags',
        'og_min', 'og_max', 'fg_min', 'fg_max',
        'abv_min', 'abv_max', 'ibu_min', 'ibu_max', 'srm_min', 'srm_max',
    ];

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_style');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
