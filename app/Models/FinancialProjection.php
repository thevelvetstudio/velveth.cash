<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialProjection extends Model
{
    use HasFactory;

    protected $fillable = ['department_id', 'created_by', 'title', 'justification', 'expected_at', 'priority', 'status', 'total_amount', 'notes'];
    protected $casts = ['expected_at' => 'date', 'total_amount' => 'decimal:2'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(FinancialProjectionItem::class); }
    public function attachments(): HasMany { return $this->hasMany(FinancialProjectionAttachment::class); }
}
