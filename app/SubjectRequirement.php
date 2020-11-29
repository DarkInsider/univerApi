<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubjectRequirement extends Model
{
    protected $fillable = ['id', 'subject_id', 'subject_required_title', 'credits_ECTS', 'semester', 'difficult'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'hidden'];
}
