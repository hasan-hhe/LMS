<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstanceState extends Model
{
    protected $table = 'instance_states';

    public $timestamps = false;

    protected $fillable = ['state'];

    public function bookInstances()
    {
        return $this->hasMany(BookInstance::class, 'state_id');
    }
}
