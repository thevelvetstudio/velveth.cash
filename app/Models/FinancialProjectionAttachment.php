<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialProjectionAttachment extends Model
{
    use HasFactory;
    protected $fillable = ['financial_projection_id', 'uploaded_by', 'file_name', 'file_path', 'mime_type', 'file_size'];
    protected $appends = ['url'];
    public function projection(): BelongsTo { return $this->belongsTo(FinancialProjection::class, 'financial_projection_id'); }
    public function getUrlAttribute(): string { return route('financial-projections.attachments.show', $this->id); }
}
