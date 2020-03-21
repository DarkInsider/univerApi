<?php

namespace App\Http\Controllers;

use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function getUser($token){
    try{
        $user = DB::table('users')->where('token', $token)->first();
    }catch (Exception $e){
        return 'err';
    }
    return $user;
}


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

        $user = getUser($request->header('token'));
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
                        ['possibility_has_roles.type', 'role']
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

                $tpm =[];
                $ids = [];
                foreach ($role as $block){
                    foreach ($block as $item){
                        if(!in_array($item->id, $ids)){
                            array_push($tpm, $item);
                            array_push($ids, $item->id);
                        }

                    }
                }
                return response(json_encode($tpm, JSON_UNESCAPED_UNICODE), 200);

            }else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }



        }
    }
}
