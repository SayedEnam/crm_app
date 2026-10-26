<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['application_id', 'uploaded_by', 'name', 'file_path', 'mime_type', 'status', 'notes'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationRecord::class, 'application_id');
    }
}
