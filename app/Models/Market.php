<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    protected $fillable = [
        'market_group_id', 'name', 'area', 'lessor', 'monthly_rent',
        'lease_start_date', 'lease_end_date', 'image_path', 'description', 'is_active', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'monthly_rent' => 'decimal:2',
            'lease_start_date' => 'date',
            'lease_end_date' => 'date',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MarketGroup::class, 'market_group_id');
    }

    public function rentPeriods(): HasMany
    {
        return $this->hasMany(MarketRentPeriod::class);
    }

    public function rentForMonth(?string $month = null): float
    {
        $month ??= now()->format('Y-m');
        $periods = $this->relationLoaded('rentPeriods') ? $this->getRelation('rentPeriods') : $this->rentPeriods()->get();
        $period = $periods->first(fn (MarketRentPeriod $candidate) => $candidate->start_month <= $month && ($candidate->end_month === null || $candidate->end_month >= $month));

        if ($period?->status === 'vacant') {
            return 0.0;
        }

        return (float) ($period?->monthly_rent ?? $this->monthly_rent);
    }
}
