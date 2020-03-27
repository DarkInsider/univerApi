<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['id', 'title', 'group_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
