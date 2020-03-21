<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['id','title'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
