<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Test_plan extends Model
{
    protected $fillable = ['id', 'type','subject_name','semester', 'zalic_or_examen', 'z_or_e_number', 'cours_projects', 'cours_work', 'leccii','laborat','practik','samostiyna_robta','weeks_in_semester','par_per_week','credits_ECTS'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}
