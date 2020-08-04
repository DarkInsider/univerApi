<?php

namespace App\Http\Controllers;

use App\Subject;
use Illuminate\Http\Request;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;
use Illuminate\Support\Facades\DB;
use App\Group;

class SubjectController extends Controller
{
    static private  function create_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        try {
            $ret =Subject::create (
                [
                    'lecturer_id' => $request->lecturer_id,
                    'department_id' => $request->department_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'hours' => $request->hours,
                    'html' => $request->html,
                    'updated_at' => $date,
                    'created_at'=> $date
                ]
            );
        } catch (\Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $ret;
        return $response;
    }

    public function create(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }
        if ($request->type === null) {
            array_push($err, 'type is required');
        }
        if ($request->hours === null) {
            array_push($err, 'hours is required');
        }
        if ($request->department_id === null) {
            array_push($err, 'department_id is required');
        } else {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if ($request->lecturer_id === null) {
            array_push($err, 'lecturer_id is required');
        } else {
            try {
                $ret = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'lecturer must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }
        try {
            $ret = DB::table('department_has_lecturers')
                ->select('department_has_lecturers.id')->where([
                    ['department_has_lecturers.lecturer_id', $request->lecturer_id],
                    ['department_has_lecturers.department_id', $request->department_id],
                    ['department_has_lecturers.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if ($ret === null) {
            array_push($err, 'lecturer must exist on selected department');
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
            $ret = SubjectController::create_subject($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 34],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag = false;
                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($facultyReq->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($facultyReq->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($request->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($request->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = SubjectController::create_subject($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


    public function get(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->department_id !== null) {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if ($request->lecturer_id !== null) {
            try {
                $ret = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'lecturer must exist');
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



        try {
            $ret3 = DB::table('students')
                ->select()->where([
                    ['students.user_id', $user->id],
                    ['students.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if($ret3 !== null){
            if ($request->lecturer_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.lecturer_id', $request->lecturer_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
            if ($request->department_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.department_id', $request->department_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }

            try {
                $ret = DB::table('subjects')
                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                    ->join('users', 'users.id', 'lecturers.user_id')
                    ->join('departments', 'departments.id', 'subjects.department_id')
                    ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                        ['subjects.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }






        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if ($request->lecturer_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.lecturer_id', $request->lecturer_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
            if ($request->department_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.department_id', $request->department_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }

            try {
                $ret = DB::table('subjects')
                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                    ->join('users', 'users.id', 'lecturers.user_id')
                    ->join('departments', 'departments.id', 'subjects.department_id')
                    ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                        ['subjects.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 33],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {

                $subjects=[];
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.faculty_id', $faculty->faculty_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.faculty_id', $faculty->faculty_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', $faculty->faculty_id],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);

                            continue;
                        } else {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.id', $user->department_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.id', $user->department_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.id', $user->department_id],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);
                            continue;
                        } else {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.type', 'subjects.hours', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.id', intval($item->scope)],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);
                            continue;
                        }
                    }
                }
            }
        }

        return response(  json_encode(Normalize::normalize($subjects), JSON_UNESCAPED_UNICODE), 200);

    }

    public function getById(Request $request, $id){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
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
        try {
            $ret = DB::table('subjects')
                ->join('lecturers', 'lecturers.id', '=', 'subjects.lecturer_id')
                ->join('users', 'users.id', '=', 'lecturers.user_id')
                ->join('departments', 'departments.id', '=', 'subjects.department_id')
                ->select('subjects.id', 'subjects.title', 'subjects.html', 'subjects.type', 'subjects.hours', 'subjects.active', 'subjects.lecturer_id','users.name as lecturer_name', 'subjects.department_id', 'departments.title as department_title')->where([
                    ['subjects.id', $id],
                    ['subjects.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
    }


    private function update_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        $inp =   [
            'lecturer_id' => $request->lecturer_id,
            'department_id' => $request->department_id,
            'type' => $request->type,
            'title' => $request->title,
            'hours' => $request->hours,
            'html' => $request->html,
            'updated_at' => $date,
        ];
        if($request->active !== null){
            $inp['active']=1;
        }
        try {
            DB::table('subjects')
                ->where('subjects.id', $request->subject_id)
                ->update(
                  $inp
                );
        } catch (Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        try {
            $ret = DB::table('subjects')
                ->select('subjects.id', 'subjects.title', 'subjects.html', 'subjects.type', 'subjects.hours', 'subjects.lecturer_id','subjects.department_id')->where([
                    ['subjects.id', $request->subject_id],
                    ['subjects.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $ret;
        return $response;
    }




    public function update(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }
        if ($request->type === null) {
            array_push($err, 'type is required');
        }
        if ($request->hours === null) {
            array_push($err, 'hours is required');
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
        if ($request->department_id === null) {
            array_push($err, 'department_id is required');
        } else {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if ($request->lecturer_id === null) {
            array_push($err, 'lecturer_id is required');
        } else {
            try {
                $ret = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'lecturer must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }
        try {
            $ret = DB::table('department_has_lecturers')
                ->select('department_has_lecturers.id')->where([
                    ['department_has_lecturers.lecturer_id', $request->lecturer_id],
                    ['department_has_lecturers.department_id', $request->department_id],
                    ['department_has_lecturers.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if ($ret === null) {
            array_push($err, 'lecturer must exist on selected department');
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
            $ret = SubjectController::update_subject($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 35],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flag1 = false;
                $flag2 = false;
                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();

                $facultyReqOld =  DB::table('subjects')
                    ->join('departments', 'departments.id', '=', 'subjects.department_id')
                    ->select('subjects.id','subjects.department_id', 'departments.faculty_id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($facultyReq->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($faculty->faculty_id) === intval($facultyReqOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($facultyReq->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($facultyReqOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($request->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($user->department_id) === intval($facultyReqOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($request->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($facultyReqOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    }
                }
                if ($flag1 && $flag2) {
                    $ret = SubjectController::update_subject($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                } else {
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private function delete_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        DB::beginTransaction();
        try {
            DB::table('subjects')
                ->where('subjects.id', $request->subject_id)
                ->update(
                    [
                        'hidden' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            DB::rollback();
            return $response;
        }
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

        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = 'Delete OK';
        DB::commit();
        return $response;
    }


    public function delete(Request $request){
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
            $ret = SubjectController::delete_subject($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 35],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flag = false;
                $facultyReqOld =  DB::table('subjects')
                    ->join('departments', 'departments.id', '=', 'subjects.department_id')
                    ->select('subjects.id','subjects.department_id', 'departments.faculty_id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($facultyReqOld->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($facultyReqOld->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($facultyReqOld->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($facultyReqOld->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = SubjectController::delete_subject($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                } else {
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


}
