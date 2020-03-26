<?php

namespace App\Http\Controllers;

use App\Possibility_has_role;
use Illuminate\Http\Request;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

use Illuminate\Support\Facades\DB;

class PossibilityHasRoleController extends Controller
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
                $ret =  DB::table('possibility_has_roles')
                    ->join('roles', 'roles.id', '=', 'possibility_has_roles.role_id')
                    ->join('possibilities', 'possibilities.id', '=', 'possibility_has_roles.possibility_id')
                    ->select('possibility_has_roles.id', 'possibility_has_roles.type', 'possibility_has_roles.scope', 'possibility_has_roles.role_id', 'roles.title as role_title', 'possibility_has_roles.possibility_id','possibilities.title as possibility_title')
                    ->where([
                        ['roles.id', $request->role_id],
                        ['possibility_has_roles.hidden', 0],
                        ['possibilities.hidden', 0],
                        ['roles.hidden', 0],
                    ])
                    ->get();

                $tRet = [];
                foreach ($ret as $item){
                    $tmp = $item;
                    if(($item->type === 'faculty') && ($item->scope !== 'own')){
                        try{
                            $rt = DB::table('faculties')->select('faculties.title')->where('faculties.id', intval($item->scope))->first();
                        }
                        catch (Exception $e){
                            $rt='err';
                        }

                        if($rt !== 'err') {
                            $tmp->scope_title = $rt->title;
                        }
                    }
                    if(($item->type === 'department') && ($item->scope !== 'own')){
                        try {
                            $rt = DB::table('departments')->select('departments.title')->where('departments.id', intval($item->scope))->first();
                        }
                        catch (Exception $e){
                            $rt='err';
                        }
                        if($rt !== 'err') {
                            $tmp->scope_title = $rt->title;
                        }
                    }
                    array_push($tRet, $tmp);
                }
            }else{
                $ret =  DB::table('possibility_has_roles')
                    ->join('roles', 'roles.id', '=', 'possibility_has_roles.role_id')
                    ->join('possibilities', 'possibilities.id', '=', 'possibility_has_roles.possibility_id')
                    ->select('possibility_has_roles.id', 'possibility_has_roles.type', 'possibility_has_roles.scope', 'possibility_has_roles.role_id', 'roles.title as role_title', 'possibility_has_roles.possibility_id','possibilities.title as possibility_title')->where([
                        ['possibility_has_roles.hidden', 0],
                        ['roles.hidden', 0],
                        ['possibilities.hidden', 0],
                    ])->get();

                $tRet = [];
                foreach ($ret as $item){
                    $tmp = $item;
                    if(($item->type === 'faculty') && ($item->scope !== 'own')){
                        try{
                            $rt = DB::table('faculties')->select('faculties.title')->where('faculties.id', intval($item->scope))->first();
                        }
                        catch (Exception $e){
                            $rt='err';
                        }

                        if($rt !== 'err') {
                            $tmp->scope_title = $rt->title;
                        }
                    }
                    if(($item->type === 'department') && ($item->scope !== 'own')){
                        try {
                            $rt = DB::table('departments')->select('departments.title')->where('departments.id', intval($item->scope))->first();
                        }
                        catch (Exception $e){
                            $rt='err';
                        }
                        if($rt !== 'err') {
                            $tmp->scope_title = $rt->title;
                        }
                    }
                    array_push($tRet, $tmp);
                }
            }
            return response(  json_encode($tRet, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }

    public function create(Request $request){
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->type === null){
            array_push($err, 'type is required');
        }
        if($request->scope === null){
            array_push($err, 'scope is required');
        }
        if($request->role_id === null){
            array_push($err, 'role_id is required');
        }else {
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
        if($request->possibility_id === null){
            array_push($err, 'possibility_id is required');
        }else {
            try{
                $ret = DB::table('possibilities')
                    ->select('possibilities.id')->where([
                        ['possibilities.id', $request->possibility_id],
                        ['possibilities.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'possibility must exist');
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
            $ret = Possibility_has_role::create(
                [
                    'type' => $request->type,
                    'scope' => $request->scope,
                    'role_id' => $request->role_id,
                    'possibility_id' => $request->possibility_id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }

    }


    public function update(Request $request){
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->type === null){
            array_push($err, 'type is required');
        }
        if($request->scope === null){
            array_push($err, 'scope is required');
        }
        if($request->possibility_has_role_id === null){
            array_push($err, 'possibility_has_role_id is required');
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select('possibility_has_roles.id')->where([
                        ['possibility_has_roles.id', $request->possibility_has_role_id],
                        ['possibility_has_roles.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'possibility_has_role must exist');
            }
        }
        if($request->role_id === null){
            array_push($err, 'role_id is required');
        }else {
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
        if($request->possibility_id === null){
            array_push($err, 'possibility_id is required');
        }else {
            try{
                $ret = DB::table('possibilities')
                    ->select('possibilities.id')->where([
                        ['possibilities.id', $request->possibility_id],
                        ['possibilities.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'possibility must exist');
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
                DB::table('possibility_has_roles')
                    ->where('possibility_has_roles.id', $request->possibility_has_role_id)
                    ->update([
                        'type' => $request->type,
                        'scope' => $request->scope,
                        'role_id' => $request->role_id,
                        'possibility_id' => $request->possibility_id,
                        'updated_at' => $date,
                    ]);
            } catch (Exception $e) {
                return 'err';
            }
            try {
                $ret =  DB::table('possibility_has_roles')
                    ->join('roles', 'roles.id', '=', 'possibility_has_roles.role_id')
                    ->join('possibilities', 'possibilities.id', '=', 'possibility_has_roles.possibility_id')
                    ->select('possibility_has_roles.id', 'possibility_has_roles.type', 'possibility_has_roles.scope', 'possibility_has_roles.role_id', 'roles.title as role_title', 'possibility_has_roles.possibility_id','possibilities.title as possibility_title')
                    ->where([
                        ['roles.id', $request->role_id],
                        ['possibility_has_roles.hidden', 0],
                    ])
                    ->first();
            } catch (Exception $e) {
                return 'err';
            }
            if($ret === 'err'){
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 500);
            }else {
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }

        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }

    }

    public function delete(Request $request){
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->possibility_has_role_id === null){
            array_push($err, 'possibility_has_role_id is required');
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select('possibility_has_roles.id')->where([
                        ['possibility_has_roles.id', $request->possibility_has_role_id],
                        ['possibility_has_roles.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'possibility_has_role must exist');
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
                DB::table('possibility_has_roles')
                    ->where('possibility_has_roles.id', $request->possibility_has_role_id)
                    ->update([
                        'hidden' => true,
                        'updated_at' => $date,
                    ]);
            } catch (Exception $e) {
                return 'err';
            }
            if($ret === 'err'){
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 500);
            }else {
                return response(  json_encode('deleted', JSON_UNESCAPED_UNICODE), 200);
            }

        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }

    }

}
