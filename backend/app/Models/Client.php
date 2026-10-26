<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'company', 'country', 'address', 'notes', 'status', 'assigned_user_id'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationRecord::class);
    }

    public function portalAccount(): HasOne
    {
        return $this->hasOne(ClientPortalAccount::class);
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
