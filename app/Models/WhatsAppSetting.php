<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    public const PRIMARY_NUMBER = '962770911991';
    protected $fillable = ['enabled', 'additional_numbers'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'additional_numbers' => 'array'];
    }

    public static function current(): self
    {
        return self::find(1) ?? new self(['enabled' => true, 'additional_numbers' => []]);
    }

    /** @return list<string> */
    public function recipients(): array
    {
        return array_values(array_unique([self::PRIMARY_NUMBER, ...($this->additional_numbers ?? [])]));
    }
}
