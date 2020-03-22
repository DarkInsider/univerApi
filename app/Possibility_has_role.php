<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Possibility_has_role extends Model
{
    protected $fillable = ['id', 'type', 'scope', 'role_id', 'possibility_id'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
