<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'unit',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class)->withPivot('price');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
