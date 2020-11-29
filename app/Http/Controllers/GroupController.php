<?php

namespace App\Http\Controllers;

use App\Choise;
use App\Group;
use App\Lecturer_has_subject;
use App\Log;
use App\Note;
use App\Plan;
use App\Subject;
use App\SubjectRequirement;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

class GroupController extends Controller
{

    private function create_group($request){
        $date = date('Y-m-d H:i:s');
        $tmpArr =[
            'code' => $request->code,
            'department_id' => $request->department_id,
            'created_at' => $date,
            'updated_at' => $date
        ];
        if($request->gradue_type !== null){
            $tmpArr['gradue_type']=$request->gradue_type;
        }
        $ret = Group::create($tmpArr);
        return $ret;
    }

    public function create(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->code === null){
            array_push($err, 'code is required');
        }
        if($request->department_id === null){
            array_push($err, 'department_id is required');

        }else {
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
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }




        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = GroupController::create_group($request);
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Group create',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 26],
                        ['possibility_has_roles.hidden', 0]
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
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
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
                    $ret =  GroupController::create_group($request);
                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Group create',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }
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

    public function get(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->department_id !== null){
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
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if($request->department_id !== null){
                try{
                    $ret =  DB::table('groups')
                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                        ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                            ['groups.hidden', 0],
                            ['departments.hidden', 0],
                            ['groups.department_id', $request->department_id],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
                foreach ($ret as $group) {
                    try {
                        $plan = DB::table('plans')
                            ->select()->where([
                                ['plans.hidden', 0],
                                ['plans.active', 1],
                                ['plans.group_id', $group->id],
                            ])->get();
                    } catch (Exception $e) {
                        return response('server error', 500);
                    }
                    if (count($plan) > 0) {
                        $group->plan_error = 0;
                    } else {
                        $group->plan_error = 1;
                    }
                }
            }else{
                try{
                    $ret =  DB::table('groups')
                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                        ->select('groups.id', 'groups.code', 'groups.gradue_type','groups.department_id', 'departments.title as department_title')->where([
                            ['groups.hidden', 0],
                            ['departments.hidden', 0],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
                foreach ($ret as $group) {
                    try {
                        $plan = DB::table('plans')
                            ->select()->where([
                                ['plans.hidden', 0],
                                ['plans.active', 1],
                                ['plans.group_id', $group->id],
                            ])->get();
                    } catch (Exception $e) {
                        return response('server error', 500);
                    }
                    if (count($plan) > 0) {
                        $group->plan_error = 0;
                    } else {
                        $group->plan_error = 1;
                    }
                }
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);


        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 25],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0){
                $groups = [];

                if($request->department_id !== null){
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
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.faculty_id', intval($faculty->faculty_id)],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    foreach ($ret as $group) {
                                        try {
                                            $plan = DB::table('plans')
                                                ->select()->where([
                                                    ['plans.hidden', 0],
                                                    ['plans.active', 1],
                                                    ['plans.group_id', $group->id],
                                                ])->get();
                                        } catch (Exception $e) {
                                            return response('server error', 500);
                                        }
                                        if (count($plan) > 0) {
                                            $group->plan_error = 0;
                                        } else {
                                            $group->plan_error = 1;
                                        }
                                    }
                                    array_push($groups, $ret);
                                }
                            }else {
                                if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    foreach ($ret as $group) {
                                        try {
                                            $plan = DB::table('plans')
                                                ->select()->where([
                                                    ['plans.hidden', 0],
                                                    ['plans.active', 1],
                                                    ['plans.group_id', $group->id],
                                                ])->get();
                                        } catch (Exception $e) {
                                            return response('server error', 500);
                                        }
                                        if (count($plan) > 0) {
                                            $group->plan_error = 0;
                                        } else {
                                            $group->plan_error = 1;
                                        }
                                    }
                                    array_push($groups, $ret);
                                }
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                if(intval($user->department_id) === intval($request->department_id)){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    foreach ($ret as $group) {
                                        try {
                                            $plan = DB::table('plans')
                                                ->select()->where([
                                                    ['plans.hidden', 0],
                                                    ['plans.active', 1],
                                                    ['plans.group_id', $group->id],
                                                ])->get();
                                        } catch (Exception $e) {
                                            return response('server error', 500);
                                        }
                                        if (count($plan) > 0) {
                                            $group->plan_error = 0;
                                        } else {
                                            $group->plan_error = 1;
                                        }
                                    }
                                    array_push($groups, $ret);
                                }
                            }else {
                                if(intval($item->scope) === intval($request->department_id)){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    foreach ($ret as $group) {
                                        try {
                                            $plan = DB::table('plans')
                                                ->select()->where([
                                                    ['plans.hidden', 0],
                                                    ['plans.active', 1],
                                                    ['plans.group_id', $group->id],
                                                ])->get();
                                        } catch (Exception $e) {
                                            return response('server error', 500);
                                        }
                                        if (count($plan) > 0) {
                                            $group->plan_error = 0;
                                        } else {
                                            $group->plan_error = 1;
                                        }
                                    }
                                    array_push($groups, $ret);
                                }
                            }
                        }
                    }
                }else {
                    foreach ($ret as $item){
                        if($item->type === 'faculty'){
                            if($item->scope === 'own'){
                                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                                    ['departments.id', $user->department_id],
                                ])->first();

                                $ret = DB::table('groups')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', intval($faculty->faculty_id)],
                                        ['departments.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                foreach ($ret as $group) {
                                    try {
                                        $plan = DB::table('plans')
                                            ->select()->where([
                                                ['plans.hidden', 0],
                                                ['plans.active', 1],
                                                ['plans.group_id', $group->id],
                                            ])->get();
                                    } catch (Exception $e) {
                                        return response('server error', 500);
                                    }
                                    if (count($plan) > 0) {
                                        $group->plan_error = 0;
                                    } else {
                                        $group->plan_error = 1;
                                    }
                                }
                                array_push($groups, $ret);

                            }else {
                                $ret = DB::table('groups')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['departments.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                foreach ($ret as $group) {
                                    try {
                                        $plan = DB::table('plans')
                                            ->select()->where([
                                                ['plans.hidden', 0],
                                                ['plans.active', 1],
                                                ['plans.group_id', $group->id],
                                            ])->get();
                                    } catch (Exception $e) {
                                        return response('server error', 500);
                                    }
                                    if (count($plan) > 0) {
                                        $group->plan_error = 0;
                                    } else {
                                        $group->plan_error = 1;
                                    }
                                }
                                array_push($groups, $ret);
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', intval($user->department_id)],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                foreach ($ret as $group) {
                                    try {
                                        $plan = DB::table('plans')
                                            ->select()->where([
                                                ['plans.hidden', 0],
                                                ['plans.active', 1],
                                                ['plans.group_id', $group->id],
                                            ])->get();
                                    } catch (Exception $e) {
                                        return response('server error', 500);
                                    }
                                    if (count($plan) > 0) {
                                        $group->plan_error = 0;
                                    } else {
                                        $group->plan_error = 1;
                                    }
                                }
                                    array_push($groups, $ret);
                            }else {
                                $ret = DB::table('groups')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('groups.id', 'groups.code','groups.gradue_type', 'groups.department_id', 'departments.title as department_title')->where([
                                        ['groups.department_id', intval($item->scope)],
                                        ['departments.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                foreach ($ret as $group) {
                                    try {
                                        $plan = DB::table('plans')
                                            ->select()->where([
                                                ['plans.hidden', 0],
                                                ['plans.active', 1],
                                                ['plans.group_id', $group->id],
                                            ])->get();
                                    } catch (Exception $e) {
                                        return response('server error', 500);
                                    }
                                    if (count($plan) > 0) {
                                        $group->plan_error = 0;
                                    } else {
                                        $group->plan_error = 1;
                                    }
                                }
                                array_push($groups, $ret);
                            }
                        }
                    }
                }

                return response(json_encode(Normalize::normalize($groups), JSON_UNESCAPED_UNICODE), 200);
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private function update_group($request){
        $date = date('Y-m-d H:i:s');



        $tmpArr = [
            'code' => $request->code,
            'department_id' => $request->department_id,
            'updated_at' => $date,
        ];
        if($request->gradue_type !== null){
            $tmpArr['gradue_type']=$request->gradue_type;
        }
        if($request->semester !== null){
            $tmpArr['semester']=$request->semester;
        }
        try {
            DB::table('groups')
                ->where('groups.id', $request->group_id)
                ->update($tmpArr);
        } catch (Exception $e) {
            return 'err';
        }


        try {
            $ret = DB::table('groups')
                ->select('groups.id', 'groups.code', 'groups.department_id')->where('groups.id', $request->group_id)->first();
        } catch (Exception $e) {
            return 'err';
        }
        return $ret;
    }



    public function update(Request $request)
    {
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->code === null){
            array_push($err, 'code is required');
        }
        if($request->semester !== null){
            if(intval($request->semester) < 1) {
                array_push($err, 'semester must be bigger to 0');
            }
        }
        if($request->group_id === null){
            array_push($err, 'group_id is required');

        }else {
            try{
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'group must exist');
            }
        }
        if($request->department_id === null){
            array_push($err, 'department_id is required');

        }else {
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
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }



        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = GroupController::update_group($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Group update',
                        'updated_at' => $date,
                        'created_at' => $date
                    ]);
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 27],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0){
                $flagFrom = false;
                $flagTo= false;

                $group = DB::table('groups')
                    ->select('groups.id', 'groups.code', 'groups.department_id')->where('groups.id', $request->group_id)->first();


                $facultyPrev = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $group->department_id],
                ])->first();
                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){

                            if(intval($faculty->faculty_id) === intval($facultyReq->faculty_id)){
                                $flagTo = true;
                            }
                            if(intval($faculty->faculty_id) === intval($facultyPrev->faculty_id)){
                                $flagFrom = true;
                            }
                            continue;
                        }else {
                            if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                $flagTo = true;
                            }
                            if(intval($item->scope) === intval($facultyPrev->faculty_id)){
                                $flagFrom = true;
                            }
                            continue;
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($request->department_id)){
                                $flagTo = true;
                            }
                            if(intval($user->department_id) === intval($facultyPrev->department_id)){
                                $flagFrom = true;
                            }
                            continue;
                        }else {
                            if(intval($item->scope)  === intval($request->department_id)){
                                $flagTo = true;
                            }
                            if(intval($item->scope)  === intval($facultyPrev->department_id)){
                                $flagFrom = true;
                            }
                            continue;
                        }
                    }
                }


                if($flagFrom && $flagTo){
                    $ret = GroupController::update_group($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Group update',
                                'updated_at' => $date,
                                'created_at' => $date
                            ]);
                        }
                        catch (Exception $e){
                            return response($e, 500);
                        }
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }
                }else{
                    return response('forbidden', 403);
                }
            }
            else{
                return response('forbidden', 403);
            }
        }
    }


    private  function delete_group($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('groups')
                ->where('groups.id', $request->group_id)
                ->update(
                    [
                        'hidden' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }


        try{
            $ret = DB::table('choises')
                ->join('students', 'students.id', 'choises.student_id')

                ->select('choises.id')
                ->where([
                    ['students.group_id', $request->group_id]
                ])->get();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        try {
            $arr =[];
            foreach ($ret as $item){
                array_push($arr, $item->id);
            }
            Choise::destroy($arr);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }


        try{
            $ret = DB::table('choises')
                ->join('notes', 'notes.id', 'choises.subject_id')

                ->join('plans', 'plans.id', 'notes.plan_id')

                ->select('choises.id')
                ->where([
                    ['plans.group_id', $request->group_id],
                    ['choises.subject_type', 'N']
                ])->get();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        $arr =[];
        foreach ($ret as $item){
            array_push($arr, $item->id);
        }
        try {
            Choise::destroy($arr);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }


        try{
            $ret = DB::table('notes')
                ->join('plans', 'plans.id', 'notes.plan_id')

                ->join('lecturer_has_subjects', 'notes.id', 'lecturer_has_subjects.subject_id')
                ->select('lecturer_has_subjects.id')
                ->where([
                    ['plans.group_id', $request->group_id]
                ])->get();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        $arr =[];
        foreach ($ret as $item){
            array_push($arr, $item->id);
        }
        try {
            Lecturer_has_subject::destroy($arr);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try{
            $ret = DB::table('notes')
                ->join('plans', 'plans.id', 'notes.plan_id')

                ->join('subject_requirements', 'notes.id', 'subject_requirements.subject_id')
                ->select('subject_requirements.id')
                ->where([
                    ['plans.group_id', $request->group_id]
                ])->get();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        $arr =[];
        foreach ($ret as $item){
            array_push($arr, $item->id);
        }
        try {
            SubjectRequirement::destroy($arr);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try{
            $ret = DB::table('notes')
                ->join('plans', 'plans.id', 'notes.plan_id')

                ->select('notes.id')
                ->where([
                    ['plans.group_id', $request->group_id]
                ])->get();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        $arr =[];
        foreach ($ret as $item){
            array_push($arr, $item->id);
        }
        try {
            Note::destroy($arr);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }


        try{
            $ret = DB::table('plans')

                ->select('plans.id')
                ->where([
                    ['plans.group_id', $request->group_id],
                ])->get();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        $arr =[];
        foreach ($ret as $item){
            array_push($arr, $item->id);
        }
        try {
            Plan::destroy($arr);
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }




        try {
            DB::table('students')
                ->where('students.group_id', $request->group_id)
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
        if($request->group_id === null){
            array_push($err, 'group_id is required');

        }else {
            try{
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'group must exist');
            }
        }

        if(count($err) > 0){
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }



        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = GroupController::delete_group($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Group delete',
                        'updated_at' => $date,
                        'created_at' => $date
                    ]);
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 28],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0){
                $flag= false;

                $group = DB::table('groups')
                    ->select('groups.id', 'groups.code', 'groups.department_id')->where('groups.id', $request->group_id)->first();

                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $group->department_id],
                ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){

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
                            if(intval($user->department_id) === intval($group->department_id)){
                                $flag = true;
                                break;
                            }

                        }else {
                            if(intval($item->scope)  === intval($group->department_id)){
                                $flag = true;
                                break;
                            }

                        }
                    }
                }
                if($flag){
                    $ret = GroupController::delete_group($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Group delete',
                                'updated_at' => $date,
                                'created_at' => $date
                            ]);
                        }
                        catch (Exception $e){
                            return response($e, 500);
                        }
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }
                }else{
                    return response('forbidden', 403);
                }

            }else{
                return response('forbidden', 403);
            }
        }
    }


}
