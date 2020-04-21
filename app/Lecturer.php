<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $fillable = ['id', 'info', 'user_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
