<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['id', 'info', 'group_id', 'user_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
