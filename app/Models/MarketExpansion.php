<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An enlargement of a market (or one of its sections) that adds rent to every rental year from start_year on.
 */
class MarketExpansion extends Model
{
    protected $fillable = ['market_section_id', 'start_year', 'added_area', 'added_annual_rent', 'notes'];

    protected function casts(): array
    {
        return ['start_year' => 'integer', 'market_section_id' => 'integer', 'added_area' => 'decimal:2', 'added_annual_rent' => 'decimal:2'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MarketSection::class, 'market_section_id');
    }
}
