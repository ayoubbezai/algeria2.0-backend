<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'evidence_id',
        'case_file_id',
        'file_name',
        'file_url',
        'file_type',
        'sha256_hash',
        'uploaded_by',
        'classification',
    ];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class);
    }
}

