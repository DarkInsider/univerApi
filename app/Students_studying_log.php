<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Students_studying_log extends Model
{
    protected $fillable = ['id','group_name','specialty','date', 'university','student_id', 'subject_title', 'credits_ECTS', 'semester', 'difficult'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
