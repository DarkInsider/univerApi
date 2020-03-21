<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['title', 'faculty_id', 'id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
