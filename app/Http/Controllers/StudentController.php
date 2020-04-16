<?php

namespace App\Http\Controllers;

use App\Student;
use Illuminate\Http\Request;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;
use App\User;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
   public function get(Request $request){
       //requests
       $err=[];
       if($request->header('token') === null){
           array_push($err, 'token is required');
       }
       if($request->group_id === null){
           array_push($err, 'group_id is required');
       } else {
           try{
               $ret = DB::table('groups')
                   ->select('groups.id')->where([
                       ['groups.id', $request->group_id],
                       ['groups.hidden', 0]
                   ])->first();
           }
           catch (Exception $e){
               return response($e, 500);
           }
           if($ret === null){
               array_push($err, 'group must exist');
           }
       }
       if(count($err) > 0){
           return response($err, 400);
       }
       $user = GetUser::get($request->header('token'));
       if ($user === 'err') {
           return response('server error', 500);
       }
       if ($user === null) {
           return response('unauthorized', 401);
       }

       if($user->id === 1){
           try {
               $ret = DB::table('students')
                   ->join('groups', 'groups.id', '=', 'students.group_id')
                   ->join('users', 'users.id', '=', 'students.user_id')
                   ->select('students.id', 'students.info', 'students.group_id', 'students.user_id', 'users.name', 'groups.code as group_code')->where([
                       ['students.group_id', $request->group_id],
                       ['students.hidden', 0]
                   ])->get();
           } catch (Exception $e) {
               return response($e, 500);
           }
           return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

       }else {
           try {
               $ret = DB::table('possibility_has_roles')
                   ->select()->where([
                       ['possibility_has_roles.role_id', $user->role_id],
                       ['possibility_has_roles.possibility_id', 21],
                       ['possibility_has_roles.hidden', 0]
                   ])->get();
           } catch (Exception $e) {
               return response($e, 500);
           }
           if(count($ret)>0) {
               $flag = false;
               $req = DB::table('groups')
                   ->join('departments', 'departments.id', '=', 'groups.department_id')
                   ->select('groups.department_id', 'departments.faculty_id')->where([
                       ['groups.id', $request->group_id],
                       ['groups.hidden', 0]
                   ])->first();
               $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                   ['departments.id', $user->department_id],
               ])->first();
               foreach ($ret as $item){
                   if($item->type === 'faculty'){
                       if($item->scope === 'own'){
                           if(intval($faculty->faculty_id) === intval($req->faculty_id)){
                               $flag = true;
                               break;
                           }
                       }else {
                           if(intval($item->scope) === intval($req->faculty_id)){
                               $flag = true;
                               break;
                           }
                       }
                   }else if($item->type === 'department'){
                       if($item->scope === 'own'){
                           if(intval($user->department_id) === intval($req->department_id)){
                               $flag = true;
                               break;
                           }
                       }else {
                           if(intval($item->scope) === intval($req->department_id)){
                               $flag = true;
                               break;
                           }
                       }
                   }
               }
               if($flag){
                   try {
                       $ret = DB::table('students')
                           ->join('groups', 'groups.id', '=', 'students.group_id')
                           ->join('users', 'users.id', '=', 'students.user_id')
                           ->select('students.id', 'students.info', 'students.group_id', 'students.user_id', 'users.name', 'groups.code as group_code')->where([
                               ['students.group_id', $request->group_id],
                               ['students.hidden', 0]
                           ])->get();
                   } catch (Exception $e) {
                       return response($e, 500);
                   }
                   return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
               }else{
                   return response('forbidden', 403);
               }
           }  else{
               return response('forbidden', 403);
           }
       }
   }


    public function create(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->info === null) {
            array_push($err, 'info is required');
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
            }
        }
        if ($request->group_id === null) {
            array_push($err, 'group_id is required');
        } else {
            try {
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'group must exist');
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

        function createStudent($request){
            $date = date('Y-m-d H:i:s');
            $ret = Student::create(
                [
                    'info' => $request->info,
                    'group_id' => $request->group_id,
                    'user_id' => $request->user_id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            return $ret;
        }
        function create_user($request){
            $date = date('Y-m-d H:i:s');
            $ret = User::create(
                [
                    'name' => $request->name,
                    'login' => $request->login,
                    'password' => md5($request->password),
                    'role_id' => $request->role_id,
                    'department_id' => $request->department_id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            return $ret;
        }



        if($user->id === 1){
            if (intval($request->flag) === 1) {
                $ret = createStudent((object)array(
                    'info' => $request->info,
                    'group_id' => $request->group_id,
                    'user_id' => $request->user_id,
                ));
            }else{
                DB::beginTransaction();
                try {
                    $group = DB::table('groups')
                        ->select('groups.id', 'groups.department_id')->where([
                            ['groups.id', $request->group_id],
                            ['groups.hidden', 0]
                        ])->first();
                } catch (Exception $e) {
                    DB::rollback();
                    return response($e, 500);
                }

                try {
                    $newUser = create_user((object)array(
                        'name' => $request->name,
                        'login' => $request->login,
                        'password' => md5($request->password),
                        'role_id' => 4,
                        'department_id' => $group->department_id,
                    ));
                }catch (Exception $e) {
                    DB::rollback();
                    return response($e, 500);
                }
                try {
                    $ret = createStudent((object)array(
                        'info' => $request->info,
                        'group_id' => $request->group_id,
                        'user_id' => $newUser->id,
                    ));
                }catch (Exception $e) {
                    DB::rollback();
                    return response($e, 500);
                }
                DB::commit();
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 22],
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
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            if(intval($faculty->faculty_id) === intval($req->faculty_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }else {
                            if(intval($item->scope) === intval($req->faculty_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($req->department_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }else {
                            if(intval($item->scope) === intval($req->department_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    }
                }
                if($flag1 && $flag2){
                    if (intval($request->flag) === 1) {
                        $ret = createStudent((object)array(
                            'info' => $request->info,
                            'group_id' => $request->group_id,
                            'user_id' => $request->user_id,
                        ));
                    }else{
                        DB::beginTransaction();
                        try {
                            $group = DB::table('groups')
                                ->select('groups.id', 'groups.department_id')->where([
                                    ['groups.id', $request->group_id],
                                    ['groups.hidden', 0]
                                ])->first();
                        } catch (Exception $e) {
                            DB::rollback();
                            return response($e, 500);
                        }

                        try {
                            $newUser = create_user((object)array(
                                'name' => $request->name,
                                'login' => $request->login,
                                'password' => md5($request->password),
                                'role_id' => 4,
                                'department_id' => $group->department_id,
                            ));
                        }catch (Exception $e) {
                            DB::rollback();
                            return response($e, 500);
                        }
                        try {
                            $ret = createStudent((object)array(
                                'info' => $request->info,
                                'group_id' => $request->group_id,
                                'user_id' => $newUser->id,
                            ));
                        }catch (Exception $e) {
                            DB::rollback();
                            return response($e, 500);
                        }
                        DB::commit();
                    }
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }



    public function update(Request $request){
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
        if ($request->info === null) {
            array_push($err, 'info is required');
        }
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
            }
        }
        if ($request->group_id === null) {
            array_push($err, 'group_id is required');
        } else {
            try {
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'group must exist');
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

        function update_student($request){
            $date = date('Y-m-d H:i:s');

            try {
                DB::table('students')
                    ->where('students.id', $request->student_id)
                    ->update(
                        [
                            'info' => $request->info,
                            'group_id' => $request->group_id,
                            'user_id' => $request->user_id,
                            'updated_at' => $date,
                        ]
                    );
            } catch (Exception $e) {
                return 'err';
            }
            try {
                $ret = DB::table('students')
                    ->select('students.id', 'students.info', 'students.group_id', 'students.user_id')->where('students.id', $request->student_id)->first();
            } catch (Exception $e) {
                return 'err';
            }
            return $ret;
        }

        if($user->id === 1){
            $ret = update_student($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else{
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 23],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $studOld= DB::table('students')
                    ->join('groups', 'groups.id', '=', 'students.group_id')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($faculty->faculty_id) === intval($studOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($studOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($user->department_id) === intval($studOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($studOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    }
                }

                if($flag2 && $flag1){
                    $ret = update_student($request);
                    if($ret === 'err'){
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



    public function delete(Request $request){
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

        function delete_student($request){
            $date = date('Y-m-d H:i:s');

            try {
                DB::table('students')
                    ->where('students.id', $request->student_id)
                    ->update(
                        [
                            'hidden' => true,
                            'updated_at' => $date,
                        ]
                    );
            } catch (Exception $e) {
                return 'err';
            }

            return 'Delete OK';
        }

        if($user->id === 1){
            $ret = delete_student($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else{
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 24],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $req = DB::table('students')
                    ->join('groups', 'groups.id', '=', 'students.group_id')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            continue;
                        }
                    }
                }

                if($flag1){
                    $ret = delete_student($request);
                    if($ret === 'err'){
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



    public function import(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->file('file') === null){
            array_push($err, 'file is required');
        }
        if($request->group_id === null){
            array_push($err, 'group_id is required');
        }else {
            try{
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'group must exist');
            }else {
                try{
                    $ret = DB::table('students')
                        ->select('students.id')->where([
                            ['students.group_id', $request->group_id],
                            ['students.hidden', 0]
                        ])->get();
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                if(count($ret) > 0) {
                    array_push($err, 'group must be empty');
                }
            }
        }
        if(count($err) > 0){
            return response($err, 400);
        }


        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }

        function import_students($request){
            $date = date('Y-m-d H:i:s');
            DB::beginTransaction();
            $array = Excel::toArray(null, request()->file('file'));
            $ret=[];
            try{
                $group = DB::table('groups')
                    ->select('groups.id', 'groups.department_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }
            $loginsDublicat = [];
            foreach ($array[0] as $item){
                try {
                    $login = DB::table('users')
                        ->select('users.login')->where([
                            ['users.login', $item[1]],
                        ])->first();
                    if($login !== null){
                        array_push( $loginsDublicat, $login);
                    }
                } catch (Exception $e){
                    DB::rollback();
                    return response($e, 500);
                }
            }
            if(count($loginsDublicat) > 0){
                DB::commit();
                $response = [];
                $response['code'] = 400;
                $response['message'] = 'login must be uniq';
                $response['data'] = $loginsDublicat;
                return $response;
            }

            foreach ($array[0] as $item){
                try{
                    $newUser = User::create(
                        [
                            'name' => $item[0],
                            'login' => $item[1],
                            'password' => md5($item[2]),
                            'role_id' => 4,
                            'department_id' => $group->department_id,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]
                    );
                }
                catch (Exception $e){
                    DB::rollback();
                    return response($e, 500);
                }
                try{
                    $tmp = Student::create(
                        [
                            'info' => $item[3],
                            'group_id' => $request->group_id,
                            'user_id' => $newUser->id,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]
                    );
                }
                catch (Exception $e){
                    DB::rollback();
                    return response($e, 500);
                }
                array_push( $ret, $tmp);
            }
            DB::commit();
            $response = [];
            $response['code'] = 200;
            $response['message'] = 'OK';
            $response['data'] = $ret;
            return $response;
        }
        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = import_students($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 22],
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
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 22) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 22) {
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
                                if (intval($item->possibility_id) === 22) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 22) {
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
                    $ret = import_students($request);
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
