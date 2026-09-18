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
        'supplier_name', 'supplier_tax_id', 'invoice_number', 'subtotal', 'tax_amount', 'discount_amount',
        'currency', 'source', 'support_path', 'support_name', 'support_mime_type',
    ];

    protected $appends = [
        'image_url',
        'support_url',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'movement_date' => 'date',
        'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'discount_amount' => 'decimal:2',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('financial-movements.image', $this->id) : null;
    }

    public function getSupportUrlAttribute(): ?string
    {
        return $this->support_path ? route('financial-movements.support', $this->id) : null;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
