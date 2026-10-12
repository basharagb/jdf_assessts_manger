<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractReminder extends Model
{
    protected $fillable = ['market_id', 'lease_end_date', 'recipient', 'status', 'attempts', 'sent_at', 'message_id', 'last_error'];

    protected function casts(): array
    {
        return ['lease_end_date' => 'date', 'sent_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
