<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationRecord extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = ['client_id', 'assigned_user_id', 'service_id', 'reference', 'status', 'started_at', 'completed_at', 'notes'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'application_id');
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
