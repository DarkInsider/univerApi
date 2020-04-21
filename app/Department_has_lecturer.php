<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Department_has_lecturer extends Model
{
    protected $fillable = ['id', 'type', 'department_id', 'lecturer_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
