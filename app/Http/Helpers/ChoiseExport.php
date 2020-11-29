<?php
namespace App\Http\Helpers;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChoiseExport implements FromCollection
{
    public function collection()
    {

        $resp=[];
        try{
            $ret1 = DB::table('choises')
                ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                ->select('subjects.title', 'choises.subject_id','choises.subject_type')
                ->where([['choises.subject_type', 'V']])
                ->distinct()
                ->get();
        }catch (Exception $e){
            $ret = [];
        }

        try{
            $ret2 = DB::table('choises')
                ->join('notes', 'notes.id', '=', 'choises.subject_id')
                ->select('notes.subject_name as title', 'choises.subject_id','choises.subject_type')
                ->where([['choises.subject_type', 'N']])
                ->distinct()
                ->get();
        }catch (Exception $e){
            $ret = [];
        }

        $ret =[];
        foreach ($ret1 as $item){
            array_push($ret, $item);
        }
        foreach ($ret2 as $item){
            array_push($ret, $item);
        }

        foreach ($ret as $sel){
            try{
                $ret2 = DB::table('choises')
                    ->join('students', 'students.id', '=', 'choises.student_id')
                    ->join('users', 'students.user_id', 'users.id')
                    ->join('groups', 'students.group_id', 'groups.id')
                    ->select('students.id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login')
                    ->where([
                        ['choises.subject_id', $sel->subject_id],
                        ['choises.hidden', 0]
                    ])
                    ->get();
            }catch (Exception $e){
                $ret2 = [];
            }
            array_push($resp, [$sel->title, $sel->subject_id]);
            array_push($resp, ['id', 'group_id', 'group_code', 'user_id', 'student_name', 'login']);

            foreach ($ret2 as $row){
                array_push($resp, [$row->id, $row->group_id, $row->group_code,  $row->user_id, $row->student_name, $row->login ]);
            }
        }
        return new Collection($resp);
    }
}
