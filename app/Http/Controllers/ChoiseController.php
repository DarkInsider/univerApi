<?php

namespace App\Http\Controllers;

use App\Choise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\ChoiseExport;
use Maatwebsite\Excel\Facades\Excel;
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

    private function clear_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        DB::beginTransaction();
        try {
            DB::table('choises')
                ->where('choises.subject_id', $request->subject_id)
                ->update(
                    [
                        'hidden' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (\Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            DB::rollback();
            return $response;
        }
        try {
            DB::table('subjects')
                ->where('subjects.id', $request->subject_id)
                ->update(
                    [
                        'active' => false,
                        'updated_at' => $date,
                    ]
                );
        } catch (\Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            DB::rollback();
            return $response;
        }

        $response['code'] = 200;
        $response['message'] = 'Delete OK';
        $response['data'] = NULL;
        DB::commit();
        return $response;
    }

    public function subjectClear(Request $request){
        //Удалить несипользуемые предметы

        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }

        if ($request->subject_id === null) {
            array_push($err, 'subject_id is required');
        } else {
            try {
                $ret = DB::table('subjects')
                    ->select('subjects.id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'subject must exist');
            }
        }

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
            $ret = ChoiseController::clear_subject($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 40],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            if(count($ret)>0 ) {
                $ret = ChoiseController::clear_subject($request);
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
            } else {
                return response('forbidden', 403);
            }
        }
    }

    private function get_choise_by_student_id($request){
        try{
            $ret = DB::table('choises')
                ->join('students', 'students.id', '=', 'choises.student_id')
                ->join('users', 'students.user_id', 'users.id')
                ->join('groups', 'students.group_id', 'groups.id')
                ->join('plans', 'plans.group_id', 'groups.id')
                ->join('notes', 'plans.id', 'notes.plan_id')
                ->select('students.id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login', 'notes.hours as hours_need')
                ->where([
                    ['students.id', $request->student_id],
                    ['plans.active', 1],
                    ['choises.hidden', 0]
                ])
                ->whereColumn([
                    ['notes.semester', 'students.semester']
                ])
                ->distinct()
                ->get();
        }catch (Exception $e){
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }

        foreach ($ret as $stud){
            $sum = 0;
            try{
                $ret2 = DB::table('choises')
                    ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                    ->select('choises.subject_id', 'subjects.title', 'subjects.hours')
                    ->where([
                        ['choises.student_id', $stud->id],
                        ['choises.hidden', 0]
                    ])
                    ->get();
            }catch (Exception $e){
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
            foreach ($ret2 as $hours){
                $sum+=$hours->hours;
            }
            $stud->hours = $sum;
            $stud->subjects = $ret2;
        }
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $ret;
        return $response;
    }

    public function getChoiseByStudentID(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
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

        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = ChoiseController::get_choise_by_student_id($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 37],
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
                $ret = ChoiseController::get_choise_by_student_id($request);
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
            } else {
                return response('forbidden', 403);
            }
        }


    }


    private function get_choises($request){
        $response = [];
        if(intval($request->type)  === 1){

            try{
                $ret = DB::table('choises')
                    ->join('students', 'students.id', '=', 'choises.student_id')
                    ->join('users', 'students.user_id', 'users.id')
                    ->join('groups', 'students.group_id', 'groups.id')
                    ->join('plans', 'plans.group_id', 'groups.id')
                    ->join('notes', 'plans.id', 'notes.plan_id')
                    ->select('students.id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login', 'notes.hours as hours_need')
                    ->where([

                        ['plans.active', 1],
                        ['choises.hidden', 0]
                    ])
                    ->whereColumn([
                        ['notes.semester', 'students.semester']
                    ])
                    ->distinct()
                    ->get();
            }catch (Exception $e){
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }

            foreach ($ret as $stud){
                $sum = 0;
                try{
                  $ret2 = DB::table('choises')
                      ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                      ->select('choises.subject_id', 'subjects.title', 'subjects.hours')
                      ->where([
                          ['choises.student_id', $stud->id],
                          ['choises.hidden', 0]
                      ])
                      ->get();
                }catch (Exception $e){
                    $response['code'] = 500;
                    $response['message'] = 'Server Error';
                    $response['data'] = $e;
                    return $response;
                }
                foreach ($ret2 as $hours){
                    $sum+=$hours->hours;
                }
                $stud->hours = $sum;
                $stud->subjects = $ret2;
            }
            $response['code'] = 200;
            $response['message'] = 'OK';
            $response['data'] = $ret;
            return $response;
        }
        if(intval($request->type)  === 2){
            try{
                $ret = DB::table('choises')
                    ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                    ->select('subjects.title', 'choises.subject_id')
                    ->where([
                        ['choises.hidden', 0]
                    ])
                    ->distinct()
                    ->get();
            }catch (Exception $e){
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }

            foreach ($ret as $subj){
                try{
                    $ret2 = DB::table('choises')
                        ->join('students', 'students.id', '=', 'choises.student_id')
                        ->join('users', 'students.user_id', 'users.id')
                        ->join('groups', 'students.group_id', 'groups.id')
                        ->select('choises.student_id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login')
                        ->where([
                            ['choises.subject_id', $subj->subject_id],
                            ['choises.hidden', 0]
                        ])
                        ->get();
                }catch (Exception $e){
                    $response['code'] = 500;
                    $response['message'] = 'Server Error';
                    $response['data'] = $e;
                    return $response;
                }
                $subj->student_count = count($ret2);
                $subj->students = $ret2;
            }
            try{
                $ret3 = DB::table('subjects')
                    ->select()
                    ->where([
                        ['subjects.active', 1],
                        ['subjects.hidden', 0]
                    ])
                    ->get();
            }catch (Exception $e){
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }

            foreach ($ret3 as $subject){
                $tmp = 0;
                $count = 0;
                foreach ($ret as $item){
                    if($item->subject_id === $subject->id){
                        $tmp = 1;
                    }
                    $count++;
                }
                if($tmp === 0){
                    $obj = (object) [] ;
                    $obj->student_count =0;
                    $obj->students =[];
                    $obj->title =$subject->title;
                    $obj->subject_id =$subject->id;

                    $ret->add($obj);
                }
            }


            $response['code'] = 200;
            $response['message'] = 'OK';
            $response['data'] = collect($ret)->sortBy('student_count')->reverse()->toArray(); ;
            return $response;
        }
        $response['code'] = 400;
        $response['message'] = 'type is wrong';
        $response['data'] = NULL;
        return $response;

    }

    public function get(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }

        if ($request->type === null) {
            array_push($err, 'type is required (1 - students vue, 2 - subjects vue');
        }

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
            $ret = ChoiseController::get_choises($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 37],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            if(count($ret)>0 ) {
                $ret = ChoiseController::get_choises($request);
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
            } else {
                return response('forbidden', 403);
            }
        }
    }

    public function export(Request $request){
        //requests
        $err = [];
        if ($request->token === null) {
            array_push($err, 'token is required');
        }

        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->token);
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            return Excel::download(new ChoiseExport(), 'choise.xlsx');
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 37],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            if(count($ret)>0 ) {
                return Excel::download(new ChoiseExport(), 'choise.xlsx');
            } else {
                return response('forbidden', 403);
            }
        }



    }
}
