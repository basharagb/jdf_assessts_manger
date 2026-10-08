<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketRentPeriod extends Model
{
    protected $fillable = [
        'start_month', 'end_month', 'monthly_rent', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['monthly_rent' => 'decimal:2'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
