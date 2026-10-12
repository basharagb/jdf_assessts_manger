<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketRentPeriod extends Model
{
    protected $fillable = [
        'start_year', 'end_year', 'annual_rent', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['annual_rent' => 'decimal:2', 'start_year' => 'integer', 'end_year' => 'integer'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
