<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_number',
        'tag',
        'title',
        'owner',
        'owner_id',
        'status',
        'notes',
        'evidence',
        'restricted',
    ];

    protected $casts = [
        'restricted' => 'boolean',
    ];

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function evidenceItems(): HasMany
    {
        return $this->hasMany(EvidenceItem::class);
    }
}

