<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = ['id', 'hours', 'semester', 'plan_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
