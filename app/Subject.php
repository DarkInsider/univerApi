<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['id', 'title', 'type', 'credits_ECTS', 'semester', 'gradue_type', 'subject_description','difficult', 'active', 'lecturer_id', 'department_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
