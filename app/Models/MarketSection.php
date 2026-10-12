<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketSection extends Model
{
    protected $fillable = ['name', 'area', 'annual_rent', 'sort_order'];

    protected function casts(): array
    {
        return ['area' => 'decimal:2', 'annual_rent' => 'decimal:2', 'sort_order' => 'integer'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function rentPeriods(): HasMany
    {
        return $this->hasMany(MarketRentPeriod::class);
    }
}
