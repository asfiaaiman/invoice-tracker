<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'tax_id',
        'address',
        'city',
        'zip_code',
        'country',
        'email',
        'phone',
        'note',
    ];

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
