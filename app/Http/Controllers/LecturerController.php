<?php

namespace App\Http\Controllers;

use App\Lecturer;
use Illuminate\Http\Request;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Department_has_lecturer;

class LecturerController extends Controller
{


    private   function create_lecturer($request){
        $date = date('Y-m-d H:i:s');

        $response = [];

        DB::beginTransaction();
        if(intval($request->flag) === 0){
            try {
                $newUser = User::create(
                    [
                        'name' => $request->name,
                        'login' => $request->login,
                        'password' => md5($request->password),
                        'role_id' => 5,
                        'department_id' => $request->department_id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
            try {
                $lecturer = Lecturer::create(
                    [
                        'info' => $request->info,
                        'user_id' => $newUser->id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
            try {
                $ret = Department_has_lecturer::create(
                    [
                        'lecturer_id' => $lecturer->id,
                        'department_id' => $request->department_id,
                        'type' => $request->type
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
        }elseif (intval($request->flag) !== 0){
            try {
                $newUser =  DB::table('users')
                    ->select()->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0]
                    ])->first();
            } catch (\Exception $e) {
                DB::rollback();
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
            try {
                $lecturer = Lecturer::create(
                    [
                        'info' => $request->info,
                        'user_id' => $newUser->id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
            try {
                $ret = Department_has_lecturer::create(
                    [
                        'lecturer_id' => $lecturer->id,
                        'department_id' => $newUser->department_id,
                        'type' => $request->type
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                return $response;
            }
        }
        DB::commit();
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $lecturer;
        return $response;

    }

    public function create(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->info === null) {
            array_push($err, 'info is required');
        }
        if ($request->type === null) {
            array_push($err, 'type is required');
        }
        if ($request->flag === null) {
            array_push($err, 'flag is required  (select existing user (1), or create new (0)');
        }else{
            if (intval($request->flag) === 1) {
                if ($request->user_id === null) {
                    array_push($err, 'user_id is required');
                }else{
                    try {
                        $ret = DB::table('users')
                            ->select('users.id')->where([
                                ['users.id', $request->user_id],
                                ['users.hidden', 0]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if ($ret === null) {
                        array_push($err, 'user must exist');
                    }else {
                        try {
                            $ret2 = DB::table('lecturers')
                                ->select('lecturers.id')->where([
                                    ['lecturers.user_id', $request->user_id],
                                    ['lecturers.hidden', 0]
                                ])->first();

                        } catch (Exception $e) {
                            return response($e, 500);
                        }
                        if ($ret2 !== null) {
                            array_push($err, 'user is already lecturer');
                        }
                    }
                }
            }else {
                if($request->name === null){
                    array_push($err, 'name is required');
                }
                if($request->login === null){
                    array_push($err, 'login is required');

                }else {
                    try{
                        $user = DB::table('users')
                            ->select('users.login')->where('users.login', $request->login)->first();
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }
                    if($user !== null){
                        array_push($err, 'login must be unique');
                    }
                }
                if($request->password === null){
                    array_push($err, 'password is required');
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
            $ret = LecturerController::create_lecturer($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 18],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->orWhere([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 14],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                if(intval($request->flag) === 0){
                    $req = DB::table('departments')
                        ->select('departments.faculty_id')->where([
                            ['departments.id', $request->department_id],
                            ['departments.hidden', 0]
                        ])->first();
                    $req-> department_id = $request->department_id;
                }else{
                    $req = DB::table('users')
                        ->join('departments', 'departments.id', '=', 'users.department_id')
                        ->select('users.department_id', 'departments.faculty_id')->where([
                            ['users.id', $request->user_id],
                            ['users.hidden', 0]
                        ])->first();
                }

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    }
                }
                if ($flag1 && $flag2) {
                    $ret = LecturerController::create_lecturer($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private function pin($request){
        $date = date('Y-m-d H:i:s');

        $response = [];
        try {
            $ret = Department_has_lecturer::create(
                [
                    'lecturer_id' => $request->lecturer_id,
                    'department_id' => $request->department_id,
                    'type' => $request->type,
                    'updated_at' => $date,
                    'created_ad'=> $date
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

    public function pinLecturerToDepartment(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
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
        if ($request->type === null) {
            array_push($err, 'type is required');
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
        if ($ret !== null) {
            array_push($err, 'lecturer is already exist on this department');
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
            $ret = LecturerController::pin($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 18],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->orWhere([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 19],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag = false;
                $req = DB::table('departments')
                    ->select('departments.faculty_id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
                $req->department_id = $request->department_id;


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag = true;
                                }
                                continue;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 18) {
                                    $flag = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag = true;
                                }
                                continue;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = LecturerController::pin($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }

    public function get(Request $request)
    {
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
            if($request->department_id !== null){
                try {
                    $ret = DB::table('lecturers')
                        ->join('users', 'users.id', '=', 'lecturers.user_id')
                        ->join('department_has_lecturers', 'department_has_lecturers.lecturer_id', '=', 'lecturers.id')
                        ->select('lecturers.id', 'lecturers.info', 'lecturers.user_id', 'users.name', 'users.login')->where([
                            ['department_has_lecturers.department_id', $request->department_id],
                            ['lecturers.hidden', 0]
                        ])->get();
                } catch (\Exception $e) {
                    return response($e, 500);
                }
            }else{
                try {
                    $ret = DB::table('lecturers')
                        ->join('users', 'users.id', '=', 'lecturers.user_id')
                        ->select('lecturers.id', 'lecturers.info', 'lecturers.user_id', 'users.name', 'users.login')->where([
                            ['lecturers.hidden', 0]
                        ])->get();
                } catch (\Exception $e) {
                    return response($e, 500);
                }
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 17],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                if($request->department_id !== null){
                    try {
                        $ret = DB::table('lecturers')
                            ->join('users', 'users.id', '=', 'lecturers.user_id')
                            ->join('department_has_lecturers', 'department_has_lecturers.lecturer_id', '=', 'lecturers.id')
                            ->select('lecturers.id', 'lecturers.info', 'lecturers.user_id', 'users.name', 'users.login')->where([
                                ['department_has_lecturers.department_id', $request->department_id],
                                ['lecturers.hidden', 0]
                            ])->get();
                    } catch (\Exception $e) {
                        return response($e, 500);
                    }
                }else{
                    try {
                        $ret = DB::table('lecturers')
                            ->join('users', 'users.id', '=', 'lecturers.user_id')
                            ->select('lecturers.id', 'lecturers.info', 'lecturers.user_id', 'users.name', 'users.login')->where([
                                ['lecturers.hidden', 0]
                            ])->get();
                    } catch (\Exception $e) {
                        return response($e, 500);
                    }
                }
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private function update_lecturer($request)
    {
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('lecturers')
                ->where('lecturers.id', $request->lecturer_id)
                ->update(
                    [
                        'info' => $request->info,
                        'user_id' => $request->user_id,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            return 'error';
        }
        try {
            $ret = DB::table('lecturers')
                ->select('lecturers.id', 'lecturers.info', 'lecturers.user_id')->where('lecturers.id', $request->lecturer_id)->first();
        } catch (Exception $e) {
            return 'error';
        }
        return $ret;
    }

    public function update(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->info === null) {
            array_push($err, 'info is required');
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
        if ($request->user_id === null) {
            array_push($err, 'user_id is required');
        } else {
            try {
                $ret = DB::table('users')
                    ->select('users.id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'user must exist');
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



        if ($user->id === 1) {
            $ret = LecturerController::update_lecturer($request);
            if($ret !== 'error'){
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }else{
                return response('server error', 500);
            }
        }else{
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 19],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                $reqOld = DB::table('lecturers')
                    ->join('users', 'users.id', '=', 'lecturers.user_id')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.department_id', 'departments.faculty_id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
                $req = DB::table('users')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.department_id', 'departments.faculty_id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0]
                    ])->first();




                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($reqOld->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($reqOld->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqOld->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($reqOld->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    }
                }

                if($flag2 && $flag1){
                    $ret =  LecturerController::update_lecturer($request);
                    if($ret === 'error'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }
                }else{
                    return response('forbidden', 403);
                }
            }
            else{
                return response('forbidden', 403);
            }

        }
    }

    private  function delete_lecturer($request)
    {
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('lecturers')
                ->where('lecturers.id', $request->lecturer_id)
                ->update(
                    [
                        'updated_at' => $date,
                        'hidden' => true
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'error';
        }
        try {
            DB::table('department_has_lecturers')
                ->where('department_has_lecturers.lecturer_id', $request->lecturer_id)
                ->delete();
        } catch (Exception $e) {
            DB::rollback();
            return 'error';
        }
        DB::commit();
        return 'Delete OK';
    }

    public function delete(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
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


        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }



        if ($user->id === 1) {
            $ret = LecturerController::delete_lecturer($request);
            if($ret !== 'error'){
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }else{
                return response('server error', 500);
            }
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 20],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag = false;
                $req = DB::table('lecturers')
                    ->join('users', 'users.id', '=', 'lecturers.user_id')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.department_id', 'departments.faculty_id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                    $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = LecturerController::delete_lecturer($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private  function pinUpdate($request){
        $date = date('Y-m-d H:i:s');

        $response = [];
        DB::beginTransaction();
        try {
            DB::table('department_has_lecturers')
                ->where('department_has_lecturers.id', $request->department_has_lecturers_id)
                ->update(
                    [
                        'lecturer_id' => $request->lecturer_id,
                        'department_id' => $request->department_id,
                        'type' => $request->type,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        DB::commit();
        try {
            $ret = DB::table('department_has_lecturers')
                ->select('department_has_lecturers.id', 'department_has_lecturers.type', 'department_has_lecturers.lecturer_id', 'department_has_lecturers.department_id')->where([
                    ['department_has_lecturers.id', $request->department_has_lecturers_id],
                    ['department_has_lecturers.hidden', 0]
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

    public function pinUpdateLecturerToDepartment(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
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
        if ($request->type === null) {
            array_push($err, 'type is required');
        }
        if ($request->department_has_lecturers_id === null) {
            array_push($err, 'department_has_lecturers_id is required');
        } else {
            try {
                $ret = DB::table('department_has_lecturers')
                    ->select('department_has_lecturers.id')->where([
                        ['department_has_lecturers.id', $request->department_has_lecturers_id],
                        ['department_has_lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department_has_lecturer must exist');
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
                    ['department_has_lecturers.id', '!=', $request->department_has_lecturers_id],
                    ['department_has_lecturers.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if ($ret !== null) {
            array_push($err, 'lecturer is already exist on this department');
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
            $ret = LecturerController::pinUpdate($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 19],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                $req = DB::table('departments')
                    ->select('departments.faculty_id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
                $req->department_id = $request->department_id;


                $reqOld = DB::table('department_has_lecturers')
                    ->join('departments', 'departments.id', '=', 'department_has_lecturers.department_id')
                    ->select('department_has_lecturers.department_id', 'departments.faculty_id')->where([
                        ['department_has_lecturers.id', $request->department_has_lecturers_id],
                        ['department_has_lecturers.hidden', 0]
                    ])->first();


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($reqOld->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($reqOld->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqOld->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($reqOld->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    }
                }
                if ($flag1 && $flag2) {
                    $ret = LecturerController::pinUpdate($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }
    function pinDelete($request){

        $response = [];
        DB::beginTransaction();
        try {
            DB::table('department_has_lecturers')
                ->where('department_has_lecturers.id', $request->department_has_lecturers_id)
                ->delete();
        } catch (Exception $e) {
            DB::rollback();
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        DB::commit();

        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = 'Delete OK';
        return $response;
    }
    public function pinDeleteLecturerToDepartment(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->department_has_lecturers_id === null) {
            array_push($err, 'department_has_lecturers_id is required');
        } else {
            try {
                $ret = DB::table('department_has_lecturers')
                    ->select('department_has_lecturers.id')->where([
                        ['department_has_lecturers.id', $request->department_has_lecturers_id],
                        ['department_has_lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department_has_lecturer must exist');
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
            $ret = LecturerController::pinDelete($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 19],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag = false;

                $req = DB::table('department_has_lecturers')
                    ->join('departments', 'departments.id', '=', 'department_has_lecturers.department_id')
                    ->select('department_has_lecturers.department_id', 'departments.faculty_id')->where([
                        ['department_has_lecturers.id', $request->department_has_lecturers_id],
                        ['department_has_lecturers.hidden', 0]
                    ])->first();


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = LecturerController::pinDelete($request);
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }
}
