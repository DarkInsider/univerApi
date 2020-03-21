<?php

namespace App\Http\Controllers;

use App\Department;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;



class DepartmentController extends Controller
{
    public function create(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->title === null){
            array_push($err, 'title is required');
        }
        if($request->faculty_id === null){
            array_push($err, 'faculty_id is required');

        }else{
            try{
                $role = DB::table('faculties')
                    ->select('faculties.id')->where('faculties.id', $request->faculty_id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($role === null){
                array_push($err, 'faculty must exist');
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

        function create_department($request){
            $date = date('Y-m-d H:i:s');
            $ret = Department::create(
                [
                    'title' => $request->title,
                    'faculty_id' => $request->faculty_id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            return $ret;
        }


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = create_department($request);
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 6],
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0){
                $flag = false;
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            if(intval($faculty->faculty_id) === intval($request->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            if(intval($item->scope) === intval($request->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if($flag){
                    $ret = create_department($request);
                    return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
               }
            }
            else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }
        }
    }


    public function update(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->title === null){
            array_push($err, 'title is required');
        }
        if($request->faculty_id !== null){
            try{
                $role = DB::table('faculties')
                    ->select('faculties.id')->where('faculties.id', $request->faculty_id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($role === null){
                array_push($err, 'faculty must exist');
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

        function update_department($request, $flag){
            $date = date('Y-m-d H:i:s');
            try {
                if($flag){
                    DB::table('departments')
                        ->where('departments.id', $request->department_id)
                        ->update([
                            'title' => $request->title,
                            'updated_at' => $date,
                            'faculty_id' => $request->faculty_id,
                        ]);
                }else{
                    DB::table('departments')
                        ->where('departments.id', $request->department_id)
                        ->update([
                            'title' => $request->title,
                            'updated_at' => $date,
                        ]);
                }

            } catch (Exception $e) {
                return 'err';
            }
            try {
                $dep = DB::table('departments')
                    ->select('departments.id', 'departments.title', 'departments.faculty_id')->where('departments.id', $request->department_id)->first();
            } catch (Exception $e) {
                return 'err';
            }
            return $dep;
        }


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if($request->faculty_id !== null){
                $dep = update_department($request, true);
            }else {
                $dep = update_department($request, false);
            }

            if($dep === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($dep, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 7],
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0){
                $flag = false;
                $facultyOfDepartment = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            if(intval($facultyOfDepartment->faculty_id) === intval($faculty->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            if(intval($item->scope) === intval($facultyOfDepartment->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }else if ($item->type === 'department'){
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
                    $ret = update_department($request, false);
                    return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
                }
            }
            else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }
        }
    }


    public function get(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->faculty_id !== null){
            try{
                $role = DB::table('faculties')
                    ->select('faculties.id')->where('faculties.id', $request->faculty_id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($role === null){
                array_push($err, 'faculty must exist');
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
            if($request->faculty_id === null){
                $ret =  DB::table('departments')
                    ->select('id', 'title', 'faculty_id')->where([
                        ['departments.hidden', 0],
                    ])->get();
            }else {
                $ret =  DB::table('departments')
                    ->select('id', 'title', 'faculty_id')->where([
                        ['departments.faculty_id', $request->faculty_id],
                        ['departments.hidden', 0],
                    ])->get();
            }

            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 5],
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
                $departments = [];
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();

                            if($request->faculty_id === null){
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.faculty_id', $faculty->faculty_id],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }else {
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.faculty_id', $faculty->faculty_id],
                                        ['departments.faculty_id', $request->faculty_id],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }

                        }else {
                            if($request->faculty_id === null){
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }else {
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['departments.faculty_id', $request->faculty_id],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }

                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if($request->faculty_id === null){
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.id', $user->department_id],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }else {
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.id', $user->department_id],
                                        ['departments.faculty_id', $request->faculty_id],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }

                        }else {
                            if($request->faculty_id === null){
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.id', intval($item->scope)],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }else {
                                array_push($departments,
                                    DB::table('departments')->select('departments.id', 'departments.title','departments.faculty_id')->where([
                                        ['departments.id', intval($item->scope)],
                                        ['departments.faculty_id', $request->faculty_id],
                                        ['hidden', 0]
                                    ])->get()
                                );
                            }

                        }
                    }
                }


                return response(json_encode(Normalize::normalize($departments), JSON_UNESCAPED_UNICODE), 200);

            }else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }



        }
    }

}
