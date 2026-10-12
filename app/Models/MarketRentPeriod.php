<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A rent period covers the rental years from start_year up to, but not including, end_year:
 * "2022 → 2023" is the single year that starts on the 2022 anniversary and ends on the 2023 one.
 * A null end_year keeps the period open until the contract ends.
 */
class MarketRentPeriod extends Model
{
    protected $fillable = [
        'market_section_id', 'start_year', 'end_year', 'annual_rent', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['annual_rent' => 'decimal:2', 'start_year' => 'integer', 'end_year' => 'integer', 'market_section_id' => 'integer'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MarketSection::class, 'market_section_id');
    }

    public function covers(int $year): bool
    {
        return $this->start_year <= $year && ($this->end_year === null || $this->end_year > $year);
    }
}
