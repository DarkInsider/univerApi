<?php

namespace App\Http\Controllers;

use App\Log;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

use App\Possibility_has_role;
use App\Role_has_role;

class RoleController extends Controller
{
    public function get(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
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
            $ret =  DB::table('roles')
                ->select('id', 'title')->where([
                    ['roles.hidden', 0],
                ])->get();
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 9],
                        ['possibility_has_roles.type', 'role'],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
                $role = [];
                foreach ($ret as $item){
                    if($item->type === 'role'){
                        if($item->scope === 'own'){
                            array_push($role,
                                DB::table('role_has_roles')
                                    ->join('roles', 'roles.id', '=', 'role_has_roles.role_id_has')
                                    ->select('roles.id', 'roles.title')->where([
                                    ['role_has_roles.role_id', $user->role_id],
                                    ['role_has_roles.hidden', 0]
                                ])->get()
                            );
                        }else {
                            array_push($role,
                                DB::table('role_has_roles')
                                    ->join('roles', 'roles.id', '=', 'role_has_roles.role_id_has')
                                    ->select('roles.id', 'roles.title')->where([
                                    ['role_has_roles.role_id', intval($item->scope)],
                                    ['role_has_roles.hidden', 0]
                                ])->get()
                            );
                        }
                    }
                }


                return response(json_encode(Normalize::normalize($role), JSON_UNESCAPED_UNICODE), 200);

            }else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }



        }
    }

    private    function create_role($request, $idd){
        $date = date('Y-m-d H:i:s');
        if($idd === false){
            try {
                $ret = Role::create(
                    [
                        'title' => $request->title,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            } catch (\Exception $e) {
                return 'err';
            }
            $tRet = [];
            $tRet['role']=$ret;
            return $tRet;
        }else {
            DB::beginTransaction();
            try {
                $ret['role'] = Role::create(
                    [
                        'title' => $request->title,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            } catch (\Exception $e) {
                DB::rollback();
                return 'err';
            }
            try {
                $pos = DB::table('possibility_has_roles')
                    ->select('possibility_has_roles.type', 'possibility_has_roles.scope', 'possibility_has_roles.possibility_id')->where([
                        ['possibility_has_roles.role_id', $idd],
                        ['possibility_has_roles.hidden', 0],
                    ])->get();
            } catch (\Exception $e) {
                DB::rollback();
                return 'err';
            }

            foreach ($pos as $possibility){
                try {
                    DB::table('possibility_has_roles')->insert([
                        'type' => $possibility->type,
                        'scope' => $possibility->scope,
                        'role_id' => $ret['role']->id,
                        'possibility_id' => $possibility->possibility_id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                } catch (\Exception $e) {
                    DB::rollback();
                    return 'err';
                }
            }
            try {
                $pos = DB::table('possibility_has_roles')
                    ->join('possibilities', 'possibilities.id', '=', 'possibility_has_roles.possibility_id')
                    ->select('possibility_has_roles.id', 'possibility_has_roles.type', 'possibility_has_roles.scope', 'possibility_has_roles.possibility_id', 'possibilities.title as possibility_title', 'possibility_has_roles.role_id')->where([
                        ['possibility_has_roles.role_id', $ret['role']->id],
                        ['possibility_has_roles.hidden', 0],
                    ])->get();
            } catch (\Exception $e) {
                DB::rollback();
                return 'err';
            }


            $tRet = [];
            foreach ($pos as $item){
                $tmp = $item;
                if(($item->type === 'faculty') && ($item->scope !== 'own')){
                    try{
                        $rt = DB::table('faculties')->select('faculties.title')->where('faculties.id', intval($item->scope))->first();
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return 'err';
                    }

                    $tmp->scope_title = $rt->title;

                }
                if(($item->type === 'department') && ($item->scope !== 'own')){
                    try {
                        $rt = DB::table('departments')->select('departments.title')->where('departments.id', intval($item->scope))->first();
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return 'err';
                    }

                    $tmp->scope_title = $rt->title;

                }
                array_push($tRet, $tmp);
            }




            $ret['possibilities']= $tRet;


            try {
                $roleHasRole = DB::table('role_has_roles')
                    ->select('role_has_roles.role_id_has')->where([
                        ['role_has_roles.role_id', $idd],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (\Exception $e) {
                DB::rollback();
                return 'err';
            }

            foreach ($roleHasRole as $role){
                try {
                    DB::table('role_has_roles')->insert([
                        'role_id_has' => $role->role_id_has,
                        'role_id' => $ret['role']->id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                } catch (\Exception $e) {
                    DB::rollback();
                    return 'err';
                }
            }

            try {
                $roleHasRole = DB::table('role_has_roles')
                    ->select('role_has_roles.id', 'role_has_roles.role_id', 'role_has_roles.role_id_has')->where([
                        ['role_has_roles.role_id', $ret['role']->id],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (\Exception $e) {
                DB::rollback();
                return 'err';
            }

            $ret['roleHasRole']= $roleHasRole;

            DB::commit();
            return $ret;
        }

    }


    public function create(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }
        if($request->role_id !== null){
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role must exist');
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



         if ($user->id === 1) {  //Если суперюзер то сразу выполняем
             if($request->role_id !== null) {
                 $ret = RoleController::create_role($request, $request->role_id);
             }else{
                 $ret = RoleController::create_role($request, false);
             }
             if ($ret === 'err'){
                 return response(json_encode('Server error', JSON_UNESCAPED_UNICODE), 500);
             }else{
                 $date = date('Y-m-d H:i:s');
                 try{
                     Log::create([
                         'user_id' => $user->id,
                         'action' => 'Role create',
                         'updated_at' => $date,
                         'created_at' => $date
                     ]);
                 }
                 catch (Exception $e){
                     return response($e, 500);
                 }
                 return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
             }


         } else {
             return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
         }
    }


    private  function update_role($request){
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('roles')
                ->where('roles.id', $request->role_id)
                ->update([
                    'title' => $request->title,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            return 'err';
        }
        try {
            $ret = DB::table('roles')
                ->select('roles.id', 'roles.title')->where('roles.id', $request->role_id)->first();
        } catch (Exception $e) {
            return 'err';
        }
        return $ret;
    }



    public function update(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }
        if($request->role_id === null){
            array_push($err, 'role_id is required');

        }else{
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role must exist');
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





        if ($user->id === 1) {  //Если суперюзер то сразу выполняем
            $ret = RoleController::update_role($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Role update',
                        'updated_at' => $date,
                        'created_at' => $date
                    ]);
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }


        } else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }

    private function delete_role($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('roles')
                ->where('roles.id', $request->role_id)
                ->update([
                    'hidden' => true,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('possibility_has_roles')
                ->where('possibility_has_roles.role_id', $request->role_id)
                ->update([
                    'hidden' => true,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('role_has_roles')
                ->where('role_has_roles.role_id', $request->role_id)
                ->update([
                    'hidden' => true,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }

        try {
            DB::table('users')
                ->where('users.role_id', $request->role_id)
                ->update([
                    'role_id' => 6,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }


        DB::commit();
        return 'Delete OK';
    }

    public function delete(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->role_id === null){
            array_push($err, 'role_id is required');

        }else{
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role must exist');
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



        if ($user->id === 1) {  //Если суперюзер то сразу выполняем
            $ret = RoleController::delete_role($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Role delete',
                        'updated_at' => $date,
                        'created_at' => $date
                    ]);
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        } else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }

}
