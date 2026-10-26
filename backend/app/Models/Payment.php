<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'application_id', 'amount', 'currency', 'status', 'method', 'reference', 'paid_at', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationRecord::class);
    }
}
