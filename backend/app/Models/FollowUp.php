<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'application_id', 'assigned_user_id', 'due_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationRecord::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return $query;
        }
        $ids = $user->hasRole('manager')
            ? User::query()->select('id')->where('id', $user->id)->orWhere('assigned_manager_id', $user->id)
            : [$user->id];

        return $query->whereIn('assigned_user_id', $ids);
    }
}
