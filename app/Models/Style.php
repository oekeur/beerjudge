<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $source
 * @property string $code
 * @property string $name
 * @property string $category_name
 */
class Style extends Model
{
    /** @var array<string, string> */
    public const SOURCE_LABELS = [
        'bjcp_beer' => 'BJCP Beer',
        'bjcp_mead' => 'BJCP Mead',
        'bjcp_cider' => 'BJCP Cider',
        'custom' => 'Custom',
    ];

    protected $fillable = [
        'source', 'code', 'category_name', 'category_description', 'name', 'version',
        'overall_impression', 'aroma', 'appearance', 'flavor', 'mouthfeel',
        'comments', 'history', 'style_comparison', 'ingredients', 'examples', 'tags',
        'og_min', 'og_max', 'fg_min', 'fg_max',
        'abv_min', 'abv_max', 'ibu_min', 'ibu_max', 'srm_min', 'srm_max',
    ];

    /**
     * @return BelongsToMany<Competition, $this>
     */
    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_style');
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
