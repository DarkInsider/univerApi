<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = ['id', 'title'];

	protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];

	protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
