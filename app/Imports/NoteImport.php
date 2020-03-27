<?php

namespace App\Imports;

use App\Note;
use Maatwebsite\Excel\Concerns\ToModel;

class NoteImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Note([
            'hours'     => $row[1],
            'semester'    => $row[0],
            'plan_id' => $row[2],
        ]);
    }
}
