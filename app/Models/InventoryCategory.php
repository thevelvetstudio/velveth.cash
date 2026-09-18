<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'department_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }
}
