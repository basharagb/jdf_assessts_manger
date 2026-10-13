<?php

namespace App\Models;

use App\AnnualLease;
use Carbon\CarbonInterface;
use Database\Factories\MarketFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Market extends Model
{
    /** @use HasFactory<MarketFactory> */
    use HasFactory;

    protected $fillable = [
        'market_group_id', 'name', 'area', 'lessor', 'annual_rent',
        'lease_start_date', 'lease_end_date', 'image_path', 'description', 'is_active', 'is_demo',
    ];

    protected function leaseStartDate(): Attribute
    {
        return Attribute::make(set: fn (CarbonInterface|string|null $value): ?string => $value === null ? null : Carbon::parse($value)->toDateString());
    }

    protected function leaseEndDate(): Attribute
    {
        return Attribute::make(set: fn (CarbonInterface|string|null $value): ?string => $value === null ? null : Carbon::parse($value)->toDateString());
    }

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

    /**
     * Separate halls of the same market, such as arrivals and departures, each with its own area and rent.
     * A market without sections is a single unit.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(MarketSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function expansions(): HasMany
    {
        return $this->hasMany(MarketExpansion::class)->orderBy('start_year')->orderBy('id');
    }

    public function hasSections(): bool
    {
        return $this->sections->isNotEmpty();
    }

    /** @return array<int|string, float> current annual rent keyed by section id, or 'market' for a single unit */
    public function currentRentBySection(): array
    {
        $today = Carbon::today('Asia/Amman');
        if ($today->lt($this->lease_start_date) || ($this->lease_end_date !== null && $today->gte($this->lease_end_date))) {
            return [];
        }
        $year = AnnualLease::currentYear($this);

        return collect(AnnualLease::ledger($this, $year, $year))->mapWithKeys(fn (array $row): array => [$row['section_id'] ?? 'market' => $row['amount']])->all();
    }

    public function currentAnnualRent(): float
    {
        return round(array_sum($this->currentRentBySection()), 2);
    }
}
