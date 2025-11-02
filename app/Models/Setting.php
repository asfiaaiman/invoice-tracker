<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'agency_id',
        'key',
        'value',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public static function get(string $key, $default = null, ?int $agencyId = null): ?string
    {
        $agencyId = $agencyId ?? auth()->user()?->agency_id ?? 1;

        $setting = static::where('agency_id', $agencyId)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    public static function set(string $key, ?string $value, ?int $agencyId = null): void
    {
        $agencyId = $agencyId ?? auth()->user()?->agency_id ?? 1;

        static::updateOrCreate(
            ['agency_id' => $agencyId, 'key' => $key],
            ['value' => $value]
        );
    }
}
