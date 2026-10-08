<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketGroup extends Model
{
    protected $fillable = ['name', 'slug', 'accent'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
    }
}
