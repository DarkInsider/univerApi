<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['id', 'code', 'department_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
