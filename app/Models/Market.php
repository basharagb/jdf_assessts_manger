<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    protected $fillable = [
        'market_group_id', 'name', 'area', 'lessor', 'annual_rent',
        'lease_start_date', 'lease_end_date', 'image_path', 'description', 'is_active', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'annual_rent' => 'decimal:2',
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

    public function currentAnnualRent(): float
    {
        $today = \Illuminate\Support\Carbon::today('Asia/Amman');
        if ($today->lt($this->lease_start_date) || ($this->lease_end_date !== null && $today->gte($this->lease_end_date))) {
            return 0.0;
        }
        $rows = \App\AnnualLease::ledger($this, \App\AnnualLease::currentYear($this), \App\AnnualLease::currentYear($this));
        return $rows[0]['amount'] ?? 0.0;
    }
}
