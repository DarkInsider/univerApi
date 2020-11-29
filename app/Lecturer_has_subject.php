<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lecturer_has_subject extends Model
{
    protected $fillable = ['id','lecturer_id', 'subject_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
