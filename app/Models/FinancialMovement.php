<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FinancialMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'concept',
        'type',
        'amount',
        'movement_date',
        'status',
        'payment_method',
        'description',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'movement_date' => 'date',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
