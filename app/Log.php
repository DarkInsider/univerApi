<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['id', 'action', 'reason', 'file', 'user_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
