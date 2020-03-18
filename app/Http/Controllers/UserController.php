<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\User;
use Illuminate\Support\Facades\DB;


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
