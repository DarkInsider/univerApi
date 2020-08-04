<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['id', 'title', 'type', 'hours', 'html', 'active', 'lecturer_id', 'department_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
