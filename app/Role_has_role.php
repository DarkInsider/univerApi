<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role_has_role extends Model
{
    protected $fillable = ['id','role_id', 'role_id_has'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
