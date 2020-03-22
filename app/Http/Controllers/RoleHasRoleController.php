<?php

namespace App\Http\Controllers;

use App\Role_has_role;
use Illuminate\Http\Request;


use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

use Illuminate\Support\Facades\DB;

class RoleHasRoleController extends Controller
{
    public function get(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->role_id !== null){
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where('roles.id', $request->role_id)->first();
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if($request->role_id !== null){
                $ret =  DB::table('role_has_roles')
                    ->join('roles', 'roles.id', '=', 'role_has_roles.role_id_has')
                    ->select('role_has_roles.id', 'role_has_roles.role_id',  'role_has_roles.role_id_has', 'roles.title')
                    ->where([
                        ['role_has_roles.hidden', 0],
                        ['role_has_roles.role_id', $request->role_id]
                    ])
                    ->get();
            }else{
                $ret =  DB::table('role_has_roles')
                    ->join('roles', 'roles.id', '=', 'role_has_roles.role_id_has')
                    ->select('role_has_roles.id', 'role_has_roles.role_id',  'role_has_roles.role_id_has', 'roles.title')
                    ->where([
                        ['role_has_roles.hidden', 0],
                    ])->get();
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }
}
