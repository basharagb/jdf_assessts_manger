<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ContractReminder extends Model
{
    protected $fillable = ['market_id', 'lease_end_date', 'recipient', 'status', 'attempts', 'sent_at', 'message_id', 'last_error'];

    protected function leaseEndDate(): Attribute
    {
        return Attribute::make(set: fn (CarbonInterface|string|null $value): ?string => $value === null ? null : Carbon::parse($value)->toDateString());
    }

    protected function casts(): array
    {
        return ['lease_end_date' => 'date', 'sent_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
