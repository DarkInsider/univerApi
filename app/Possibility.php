<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Possibility extends Model
{
    protected $fillable = ['id', 'title'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
