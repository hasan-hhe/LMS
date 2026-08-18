<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointSetting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value'];
}
