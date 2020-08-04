<?php

namespace App\Http\Controllers;

use App\Department;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;



class DepartmentController extends Controller
{
    public function get(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->faculty_id !== null){
            try{
                $ret = DB::table('faculties')
                    ->select('faculties.id')->where([
                        ['faculties.id', $request->faculty_id],
                        ['faculties.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
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



        try {
            $ret3 = DB::table('students')
                ->select()->where([
                    ['students.user_id', $user->id],
                    ['students.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if($ret3 !== null){
            $ret =  DB::table('departments')
                ->select('id', 'title', 'faculty_id')->where([
                    ['departments.hidden', 0],
                ])->get();
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
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
                        ['possibility_has_roles.hidden', 0]
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


    private function create_department($request){
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
                $ret = DB::table('faculties')
                    ->select('faculties.id')->where([
                        ['faculties.id', $request->faculty_id],
                        ['faculties.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
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
            $ret = DepartmentController::create_department($request);
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 6],
                        ['possibility_has_roles.hidden', 0]
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
                    $ret =  DepartmentController::create_department($request);
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


    private  function update_department($request){
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('departments')
                ->where('departments.id', $request->department_id)
                ->update([
                    'title' => $request->title,
                    'updated_at' => $date,
                    'faculty_id' => $request->faculty_id,
                ]);
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
        if($request->faculty_id === null){
            array_push($err, 'faculty_id is required');
        }else{
            try{
                $ret = DB::table('faculties')
                    ->select('faculties.id')->where([
                        ['faculties.id', $request->faculty_id],
                        ['faculties.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'faculty must exist');
            }
        }
        if($request->department_id === null){
            array_push($err, 'department_id is required');
        }else{
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
            $dep =  DepartmentController::update_department($request);
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
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0){
                $flag = false;
                $flagFrom = false;
                $flagTo = false;

                $departmentPrev = DB::table('departments')
                    ->select('departments.id', 'departments.title', 'departments.faculty_id')->where('departments.id', $request->department_id)->first();

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();


                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            if(intval($departmentPrev->faculty_id) === intval($userFaculty->faculty_id)){
                                $flagFrom = true;
                            }
                            if(intval($request->faculty_id) === intval($userFaculty->faculty_id)){
                                $flagTo = true;
                            }
                            continue;
                        }else {
                            if(intval($departmentPrev->faculty_id) === intval($item->scope)){
                                $flagFrom = true;
                            }
                            if(intval($request->faculty_id) === intval($item->scope)){
                                $flagTo = true;
                            }
                            continue;
                        }
                    }else if ($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($departmentPrev->id) === intval($user->department_id)){
                                $flagFrom = true;
                            }
                            if(intval($request->department_id) === intval($user->department_id)){
                                $flagTo = true;
                            }
                            continue;
                        }else {
                            if(intval($departmentPrev->id) === intval($item->scope)){
                                $flagFrom = true;
                            }
                            if(intval($request->department_id) === intval($item->scope)){
                                $flagTo = true;
                            }
                        }
                    }
                }
                if($flagFrom && $flagTo){
                    $ret =  DepartmentController::update_department($request);
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


    private function delete_department($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('departments')
                ->where('departments.id', $request->department_id)
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
                ->where('users.department_id', $request->department_id)
                ->update([
                    'users.hidden' => true,
                    'users.updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('groups')
                ->where('groups.department_id', $request->department_id)
                ->update([
                    'groups.hidden' => true,
                    'groups.updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('students')
                ->join('groups', 'groups.id', 'students.group_id')
                ->where('groups.department_id', $request->department_id)
                ->update(
                    [
                        'students.hidden' => true,
                        'students.updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }

        try {
            DB::table('plans')
                ->join('groups', 'groups.id', 'plans.group_id')
                ->where('groups.department_id', $request->department_id)
                ->update(
                    [
                        'plans.hidden' => true,
                        'plans.updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('notes')
                ->join('plans', 'plans.id', '=', 'notes.plan_id')
                ->join('groups', 'groups.id', 'plans.group_id')
                ->where('groups.department_id', $request->department_id)
                ->update(
                    [
                        'notes.hidden' => true,
                        'notes.updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }


        DB::commit();
        return 'Delete OK';
    }

    public function delete(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->department_id === null){
            array_push($err, 'department_id is required');

        }else{
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
            $ret = DepartmentController::delete_department($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 8],
                        ['possibility_has_roles.hidden', 0]
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
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
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
                    $ret = DepartmentController::delete_department($request);
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

}
