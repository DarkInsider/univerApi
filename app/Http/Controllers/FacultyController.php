<?php

namespace App\Http\Controllers;

use App\Faculty;
use Illuminate\Http\Request\FacultyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;





class FacultyController extends Controller
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
                $ret =  DB::table('faculties')
                    ->select('id', 'title')->where([
                        ['faculties.hidden', 0],
                    ])->get();
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 1],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
                $faculties = [];
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            array_push($faculties,
                                DB::table('faculties')->select('faculties.id', 'faculties.title')->where([
                                    ['faculties.id', $faculty->faculty_id],
                                    ['hidden', 0]
                                ])->get()
                            );
                        }else {
                            array_push($faculties,
                                DB::table('faculties')->select('faculties.id', 'faculties.title')->where([
                                    ['faculties.id', intval($item->scope)],
                                    ['hidden', 0]
                                ])->get()
                            );
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            array_push($faculties,
                                DB::table('faculties')->select('faculties.id', 'faculties.title')->where([
                                    ['faculties.id', $faculty->faculty_id],
                                    ['hidden', 0]
                                ])->get()
                            );
                        }else {
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', intval($item->scope)],
                            ])->first();
                            array_push($faculties,
                                DB::table('faculties')->select('faculties.id', 'faculties.title')->where([
                                    ['faculties.id', $faculty->faculty_id],
                                    ['hidden', 0]
                                ])->get()
                            );
                        }
                    }
                }

                return response(json_encode(Normalize::normalize($faculties), JSON_UNESCAPED_UNICODE), 200);
            }else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }
        }
    }



    private function create_faculty($request){
        $date = date('Y-m-d H:i:s');
        $ret = Faculty::create(
            [
                'title' => $request->title,
                'created_at' => $date,
                'updated_at' => $date,
            ]
        );
        return $ret;
    }


    public function create(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }

        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }





        if ($user->id === 1) {  //Если суперюзер то сразу выполняем
            $fac = FacultyController::create_faculty($request);
            return response(json_encode($fac, JSON_UNESCAPED_UNICODE), 200);
        } else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 2],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $fac = FacultyController::create_faculty($request);
                return response(json_encode($fac, JSON_UNESCAPED_UNICODE), 200);

            }else {
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }
        }
    }


    private function update_faculty($request){
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('faculties')
                ->where('faculties.id', $request->faculty_id)
                ->update(['title' => $request->title, 'updated_at' => $date]);
        } catch (Exception $e) {
            return 'err';
        }
        try {
            $fac = DB::table('faculties')
                ->select('faculties.id', 'faculties.title')->where('faculties.id', $request->faculty_id)->first();
        } catch (Exception $e) {
            return 'err';
        }
        return $fac;
    }



    public function update(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }
        if ($request->faculty_id === null) {
            array_push($err, 'faculty_id is required');
        } else {
            try {
                $fac = DB::table('faculties')
                    ->select('faculties.id')->where([
                        ['faculties.id', $request->faculty_id],
                        ['faculties.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($fac === null) {
                array_push($err, 'faculty must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }






        if ($user->id === 1) {  //Если суперюзер то сразу выполняем
            $fac = FacultyController::update_faculty($request);
            if($fac === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($fac, JSON_UNESCAPED_UNICODE), 200);
            }

        } else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 3],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
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
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            if(intval($faculty->faculty_id) === intval($request->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', intval($item->scope)],
                            ])->first();
                            if(intval($faculty->faculty_id) === intval($request->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if($flag){
                    $fac = FacultyController::update_faculty($request);
                    if($fac === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        return response(json_encode($fac, JSON_UNESCAPED_UNICODE), 200);
                    }
                }else{
                    return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
                }
            }else{
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }
        }
    }


    private  function delete_faculty($request)
    {
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('faculties')
                ->where('id', $request->id)
                ->update([
                    'hidden' => true,
                    'updated_at' => $date,
                ]);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('departments')
                ->where('faculty_id', $request->id)
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
                ->join('departments', 'departments.id', '=', 'users.department_id')
                ->where('departments.faculty_id', $request->id)
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
                ->join('departments', 'departments.id', '=', 'groups.department_id')
                ->where('departments.faculty_id', $request->id)
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
                ->join('departments', 'departments.id', '=', 'groups.department_id')
                ->where('departments.faculty_id', $request->id)
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
                ->join('departments', 'departments.id', '=', 'groups.department_id')
                ->where('departments.faculty_id', $request->id)
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
                ->join('departments', 'departments.id', '=', 'groups.department_id')
                ->where('departments.faculty_id', $request->id)
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
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->id === null) {
            array_push($err, 'id is required');
        } else {
            try {
                $fac = DB::table('faculties')
                    ->select('faculties.id')->where([
                        ['faculties.id', $request->id],
                        ['faculties.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($fac === null) {
                array_push($err, 'faculty must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }




        if ($user->id === 1) {
            $ret = FacultyController::delete_faculty($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        } else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 4],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flag = false;
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            if (intval($faculty->faculty_id) === intval($request->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($request->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', $user->department_id],
                            ])->first();
                            if (intval($faculty->faculty_id) === intval($request->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                ['departments.id', intval($item->scope)],
                            ])->first();
                            if (intval($faculty->faculty_id) === intval($request->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = FacultyController::delete_faculty($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }
                } else {
                    return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
                }
            }
            else {
                return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
            }
        }
    }
}
