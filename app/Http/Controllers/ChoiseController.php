<?php

namespace App\Http\Controllers;

use App\Choise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Usf;
class ChoiseController extends Controller
{


    private function create_choise($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        DB::beginTransaction();
        $rets = [];
        foreach ($request->subject_ids as $subject){
            try {
                $ret =Choise::create (
                    [
                        'date' => $date,
                        'subject_id' => intval($subject) ,
                        'student_id' => intval($request->student_id),
                        'updated_at' => $date,
                        'created_at'=> $date
                    ]
                );
            } catch (\Exception $e) {
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                DB::rollback();
                return $response;
            }
            array_push($rets, $ret);
        }

        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $rets;
        DB::commit();
        return $response;
    }


    public function create(Request $request){
        //Массив с выбраными предметами студента
        //requests
        $err = [];
        $hours = 0;
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->subject_ids === null) {
            array_push($err, 'subject_ids is required (Array)');
        } else {
            $flag = false;
            foreach ($request->subject_ids as $subject){
                try {
                    $ret = DB::table('subjects')
                        ->select('subjects.id', 'subjects.hours')->where([
                            ['subjects.id', $subject],
                            ['subjects.hidden', 0]
                        ])->first();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                if ($ret === null) {
                    $flag = true;
                }else {
                    $hours += $ret->hours;
                }
            }
            try {
                $ret2 = DB::table('choises')
                    ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                    ->select('subjects.hours')->where([
                        ['choises.student_id', $request->student_id],
                        ['choises.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            foreach ($ret2 as $sub){
                $hours+= $sub->hours;
            }

            if ($flag) {
                array_push($err, 'subjects must exists');
            }
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $ret = DB::table('students')
                    ->select('students.id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'student must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }


        try {
            $ret = DB::table('students')
                ->select()->where([
                    ['students.id', $request->student_id],
                    ['students.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }

        try {
            $ret = DB::table('students')
                ->join('plans', 'plans.group_id', '=', 'students.group_id')
                ->join('notes', 'plans.id', '=', 'notes.plan_id')
                ->select()->where([
                    ['students.id', $request->student_id],
                    ['plans.active', 1],
                    ['notes.semester', $ret->semester],
                    ['students.hidden', 0],
                    ['plans.hidden', 0],
                    ['notes.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if ($ret === null) {
            array_push($err, 'operation not allowed');
        }else{
            if ($ret->hours !== $hours) {
                array_push($err, 'hours not match');
            }
        }



//        $hours=0;
//        foreach ($ret2 as $sub){
//            $hours+= $sub->hours;
//        }
//
//        if ($ret->hours === $hours) {
//            array_push($err, 'student is already select subjects');
//        }



        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = ChoiseController::create_choise($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 38],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            try{
                $ret2 = DB::table('students')
                    ->select()->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            if(count($ret)>0 || ($ret2->user_id === $user->id)) {
                $ret = ChoiseController::create_choise($request);
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
            } else {
                return response('forbidden', 403);
            }
        }
    }

    public function delete(Request $request){
        //Удалить несипользуемые предметы
    }



    public function get(Request $request){

        //Вывести списки виртуальных груп, вывести предметы по количеству студентов на них
    }
}
