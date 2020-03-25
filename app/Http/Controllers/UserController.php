<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\User;
use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;



class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::all();
    }
    public function block()
    {
        return 'Method not allowed';
    }

    public function login(Request $request)
    {
        $err=[];
        if($request->login === null){
            array_push($err, 'login is required');
        }
        if($request->password === null){
            array_push($err, 'password is required');
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $user = DB::table('users')->where([['login', $request->login], ['password', md5($request->password)], ['hidden', 0]])->first();
        }
        catch (Exception $e){
            return response($e, 500);
        }

        if($user !== null){
            $token = 'tok '.md5(''.$user->name.rand(0,100000));

            try{
                  DB::table('users')
                    ->where('id', $user->id)
                    ->update(['token' => $token]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            try{
                $user = DB::table('users')
                    ->join('roles', 'roles.id', '=', 'users.role_id')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.id', 'users.name', 'users.email', 'users.token', 'users.role_id', 'roles.title as role_name', 'users.department_id', 'departments.title as department_name')->where('users.id', $user->id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }



            return response(json_encode($user), 200);
        }else{
            return response(json_encode($user), 404);
        }


    }


    public function logout(Request $request)
    {
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $date = date('Y-m-d H:i:s');
            DB::table('users')
                ->where('users.token', $request->header('token'))
                ->update([
                    'token' => null,
                    'updated_at' => $date,
                ]);
        }
        catch (Exception $e){
            return response(  json_encode('Server error', JSON_UNESCAPED_UNICODE), 500);
        }
        return response(  json_encode('Logout OK', JSON_UNESCAPED_UNICODE), 200);
    }


    public function getUserInfoByToken(Request $request)
    {
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $user = DB::table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->join('departments', 'departments.id', '=', 'users.department_id')
                ->select('users.id', 'users.login', 'users.name', 'users.email', 'users.token', 'users.role_id', 'roles.title as role_title', 'users.department_id', 'departments.title as department_title')->where('users.token', $request->header('token'))->first();
        }
        catch (Exception $e){
            return response(  json_encode('Server error', JSON_UNESCAPED_UNICODE), 500);
        }
        return response(  json_encode($user, JSON_UNESCAPED_UNICODE), 200);
    }



    public function create(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
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
        if($request->role_id === null){
            array_push($err, 'role_id is required');

        }else{
            try{
                $role = DB::table('roles')
                    ->select('roles.id')->where('roles.id', $request->role_id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($role === null){
                array_push($err, 'role must exist');
            }
        }
        if($request->department_id === null){
            array_push($err, 'department_id is required');

        }else{
            try{
                $role = DB::table('departments')
                    ->select('departments.id')->where('departments.id', $request->department_id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($role === null){
                array_push($err, 'department must exist');
            }
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
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


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = create_user($request);
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 14],
                        ['role_has_roles.role_id_has', $request->role_id],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0){
                $flag = false;
                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();

                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            if(intval($faculty->faculty_id) === intval($facultyReq->faculty_id)){
                               $flag = true;
                               break;
                            }
                        }else {
                            if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($request->department_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            if(intval($item->scope) === intval($request->department_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }
                }


                if($flag){
                    $ret = create_user($request);
                    return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response('forbidden', 403);
                }
            }
            else{
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
        if($request->department_id !== null){
            try{
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'department must exist');
            }
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
        }

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if($request->department_id !== null){
                try{
                    $ret =  DB::table('users')
                        ->join('departments', 'departments.id', '=', 'users.department_id')
                        ->join('roles', 'roles.id', '=', 'users.role_id')
                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                            ['users.hidden', 0],
                            ['users.department_id', $request->department_id],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
            }else{
                try{
                    $ret =  DB::table('users')
                        ->join('departments', 'departments.id', '=', 'users.department_id')
                        ->join('roles', 'roles.id', '=', 'users.role_id')
                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                            ['users.hidden', 0],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 13],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

           // return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

            if(count($ret)>0) {
                $users = [];
                if($request->department_id !== null){
                    $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                        ['departments.id', $request->department_id],
                    ])->first();
                    foreach ($ret as $item){
                        if($item->type === 'faculty'){
                            if($item->scope === 'own'){
                                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                    ['departments.id', $user->department_id],
                                ])->first();
                                if(intval($faculty->faculty_id) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['departments.faculty_id', intval($facultyReq->faculty_id)],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }else {
                                if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['departments.faculty_id', intval($facultyReq->faculty_id)],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                if(intval($user->department_id) === intval($request->department_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }else {
                                if(intval($item->scope) === intval($request->department_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }
                        }
                    }
                }else{
                    foreach ($ret as $item){
                        if($item->type === 'faculty'){
                            if($item->scope === 'own'){
                                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                    ['departments.id', $user->department_id],
                                ])->first();
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['departments.faculty_id', intval($faculty->faculty_id)],
                                            ['users.role_id', intval($item->role_id_has)],
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }else {
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['users.role_id', intval($item->role_id_has)],
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', intval($user->department_id)]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }else {
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', intval($item->scope)]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }
                        }
                    }
                }
                return response(  json_encode(Normalize::normalize($users), JSON_UNESCAPED_UNICODE), 200);

            }else{
                return response('forbidden', 403);
            }
        }


    }

    public function update(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->name === null) {
            array_push($err, 'name is required');
        }
        if ($request->password === null) {
            array_push($err, 'password is required');
        }
        if ($request->user_id === null) {
            array_push($err, 'user_id is required');

        } else {
            try {
                $ret = DB::table('users')
                    ->select('users.id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0],
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'user must exist');
            }else{
                if ($request->login === null) {
                    array_push($err, 'login is required');

                } else {
                    try {
                        $ret = DB::table('users')
                            ->select('users.login')->where([['users.login', $request->login],['users.id', '!=', $request->user_id]])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if ($ret !== null) {
                        array_push($err, 'login must be unique');
                    }
                }
            }
        }
        if ($request->role_id === null) {
            array_push($err, 'role_id is required');

        } else {
            try {
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0],
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'role must exist');
            }
        }
        if ($request->department_id === null) {
            array_push($err, 'department_id is required');

        } else {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0],
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
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
        }

        function update_user($request){
            $date = date('Y-m-d H:i:s');
            try {
                DB::table('users')
                    ->where('users.id', $request->user_id)
                    ->update(
                        [
                            'name' => $request->name,
                            'login' => $request->login,
                            'password' => md5($request->password),
                            'role_id' => $request->role_id,
                            'department_id' => $request->department_id,
                            'updated_at' => $date,
                        ]
                    );
            } catch (Exception $e) {
                return 'err';
            }
            try {
                $ret = DB::table('users')
                    ->select('users.id', 'users.name', 'users.login', 'users.role_id', 'users.department_id')->where('users.id', $request->user_id)->first();
            } catch (Exception $e) {
                return 'err';
            }
            return $ret;
        }


        if($user->id === 1){
            $ret = update_user($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else{
            try {
                $ret = DB::table('possibility_has_roles')
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 15],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

            //return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

            if(count($ret)>0) {


                $flagFrom = false;
                $flagTo = false;


                $userPrev = DB::table('users')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.id', 'users.name', 'users.login', 'users.role_id', 'users.department_id', 'departments.faculty_id')->where('users.id', $request->user_id)->first();

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                $facultyReq = DB::table('departments')
                    ->select('departments.id', 'departments.faculty_id')->where([
                        ['departments.id', $request->department_id],
                    ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if ((intval($userFaculty->faculty_id) === intval($facultyReq->faculty_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($userFaculty->faculty_id) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if ((intval($item->scope) === intval($facultyReq->faculty_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($item->scope) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if ((intval($user->department_id) === intval($request->department_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($user->department_id) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if ((intval($item->scope) === intval($request->department_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($item->scope) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    }
                }
                if ($flagFrom && $flagTo) {
                    $ret = update_user($request);
                    if ($ret === 'err') {
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    } else {
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }
                } else {
                    return response('forbidden', 403);
                }
            }else{
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
        if ($request->user_id === null) {
            array_push($err, 'user_id is required');

        } else {
            try {
                $ret = DB::table('users')
                    ->select('users.id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0],
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
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
        }

        function delete_user($request){
            $date = date('Y-m-d H:i:s');
            try {
                DB::table('users')
                    ->where('users.id', $request->user_id)
                    ->update(
                        [
                            'hidden' => true,
                            'token' => NULL,
                            'updated_at' => $date,
                        ]
                    );
            } catch (Exception $e) {
                return 'err';
            }
            return 'Delete OK';
        }

        if($user->id === 1){
            $ret = delete_user($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 16],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

            //return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

            if (count($ret) > 0) {
                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                $userPrev = DB::table('users')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.id', 'users.name', 'users.login', 'users.role_id', 'users.department_id', 'departments.faculty_id')->where('users.id', $request->user_id)->first();

                $flag = false;

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if ((intval($userFaculty->faculty_id) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if ((intval($item->scope) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if ((intval($user->department_id) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if ((intval($item->scope) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }

                if ($flag) {
                    $ret = delete_user($request);
                    if ($ret === 'err') {
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    } else {
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }
                } else {
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }




    }



}
