<?php

namespace App\Http\Controllers;

use App\Log;
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if($request->role_id !== null){
                $ret =  DB::table('role_has_roles')
                    ->join('roles', 'roles.id', '=', 'role_has_roles.role_id_has')
                    ->select('role_has_roles.id', 'role_has_roles.role_id',  'role_has_roles.role_id_has', 'roles.title as role_has_title')
                    ->where([
                        ['role_has_roles.hidden', 0],
                        ['roles.hidden', 0],
                        ['role_has_roles.role_id', $request->role_id]
                    ])
                    ->get();
            }else{
                $ret =  DB::table('role_has_roles')
                    ->join('roles', 'roles.id', '=', 'role_has_roles.role_id_has')
                    ->select('role_has_roles.id', 'role_has_roles.role_id',  'role_has_roles.role_id_has', 'roles.title as role_has_title')
                    ->where([
                        ['role_has_roles.hidden', 0],
                        ['roles.hidden', 0],
                    ])->get();
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }
    public function create(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->role_id === null){
            array_push($err, 'role_id is required');
        }else {
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0],
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role must exist');
            }
        }
        if($request->role_id_has === null){
            array_push($err, 'role_id_has is required');
        }else {
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id_has],
                        ['roles.hidden', 0],
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $date = date('Y-m-d H:i:s');
                $ret = Role_has_role::create(
                    [
                        'role_id' => $request->role_id,
                        'role_id_has' => $request->role_id_has,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'RoleHasRole create',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }
    public function update(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->role_has_role_id === null){
            array_push($err, 'role_has_role_id is required');
        }else {
            try{
                $ret = DB::table('role_has_roles')
                    ->select('role_has_roles.id')->where([
                        ['role_has_roles.id', $request->role_has_role_id],
                        ['role_has_roles.hidden', 0],
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role_has_role must exist');
            }
        }
        if($request->role_id === null){
            array_push($err, 'role_id is required');
        }else {
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0],
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role must exist');
            }
        }
        if($request->role_id_has === null){
            array_push($err, 'role_id_has is required');
        }else {
            try{
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id_has],
                        ['roles.hidden', 0],
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $date = date('Y-m-d H:i:s');
            try {
                DB::table('role_has_roles')
                    ->where('role_has_roles.id', $request->role_has_role_id)
                    ->update([
                        'role_id' => $request->role_id,
                        'role_id_has' => $request->role_id_has,
                        'updated_at' => $date,
                    ]);
            } catch (Exception $e) {
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }
            try {
                $ret = DB::table('role_has_roles')
                    ->select('role_has_roles.id', 'role_has_roles.role_id',  'role_has_roles.role_id_has')->where('role_has_roles.id', $request->role_has_role_id)->first();
            } catch (Exception $e) {
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'RoleHasRole update',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }

    public function delete(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->role_has_role_id === null){
            array_push($err, 'role_has_role_id is required');
        }else {
            try{
                $ret = DB::table('role_has_roles')
                    ->select('role_has_roles.id')->where([
                        ['role_has_roles.id', $request->role_has_role_id],
                        ['role_has_roles.hidden', 0],
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'role_has_role must exist');
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
            $date = date('Y-m-d H:i:s');
            try {
                DB::table('role_has_roles')
                    ->where('role_has_roles.id', $request->role_has_role_id)
                    ->update([
                        'hidden' => true,
                        'updated_at' => $date,
                    ]);
            } catch (Exception $e) {
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'RoleHasRole delete',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            return response(json_encode('deleted', JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }


}
