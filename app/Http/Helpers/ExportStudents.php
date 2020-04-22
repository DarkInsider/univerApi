<?php
namespace App\Http\Helpers;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExportStudents implements FromCollection
{


    protected $group_id;

    public function __construct(int $group_id)
    {
        $this->group_id = $group_id;
    }

    public function collection()
    {

        try{
            $ret = DB::table('students')
                ->join('users', 'students.user_id', 'users.id')
                ->join('groups', 'students.group_id', 'groups.id')
                ->select('students.id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login')
                ->where([
                    ['students.group_id', $this->group_id],
                    ['students.hidden', 0]
                ])
                ->get();
        }catch (Exception $e){
            $ret = [];
        }
        $retArray = [['id', 'group_id', 'group_code', 'user_id', 'student_name', 'login']];

        foreach ($ret as $row){
            array_push($retArray, [$row->id, $row->group_id, $row->group_code,  $row->user_id, $row->student_name, $row->login ]);
        }

        return new Collection($retArray);
    }
}
