<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'sku',
        'category',
        'quantity',
        'minimum_stock',
        'unit_cost',
        'location',
        'status',
        'notes',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('inventory-items.image', $this->id) : null;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
