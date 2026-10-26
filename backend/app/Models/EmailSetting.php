<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = ['host', 'port', 'username', 'password', 'scheme', 'from_address', 'from_name'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted', 'port' => 'integer'];
    }
}
