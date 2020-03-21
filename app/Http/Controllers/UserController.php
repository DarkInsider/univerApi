<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\User;
use Illuminate\Support\Facades\DB;

function getUser($token){
    try{
        $user = DB::table('users')->where('token', $token)->first();
    }catch (Exception $e){
        return 'err';
    }
    return $user;
}


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
            $user = DB::table('users')->where([['login', $request->login], ['password', md5($request->password)]])->first();
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

        $user = getUser($request->header('token'));
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





    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $ret = User::create($request->validated());
        return $ret;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $ret = User::findOrFail($user);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $id)
    {
        $ret = User::findOrFail($id);
        $ret->fill($request->except(['id']));
        $ret->save();
        return response()->json($ret);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $choise
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserRequest $request, $id)
    {
        $ret = User::findOrFail($id);
        if($ret->delete()) return response(null, 204);
    }
}
