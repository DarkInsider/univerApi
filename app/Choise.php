<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Choise extends Model
{
    protected $fillable = ['date', 'subject_id', 'student_id', 'semester'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
