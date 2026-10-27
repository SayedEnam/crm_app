<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    protected $fillable = ['app_name', 'company_name', 'logo_path'];
}
