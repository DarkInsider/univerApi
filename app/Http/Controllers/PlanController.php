<?php

namespace App\Http\Controllers;

use App\Choise;
use App\Lecturer_has_subject;
use App\Log;
use App\Plan;
use App\SubjectRequirement;
use Illuminate\Http\Request;
use App\Note;
use App\Imports\NoteImport;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

class PlanController extends Controller
{
    public function get(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->group_id !== null){
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

        if($user->id === 1){
            if($request->group_id !== null){
                try {
                    $ret = DB::table('plans')
                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                        ->select('plans.id', 'plans.title', 'plans.group_id', 'plans.active' , 'groups.code as group_code')->where([
                            ['plans.group_id', $request->group_id],
                            ['plans.hidden', 0]
                        ])->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
            }else{
                try {
                    $ret = DB::table('plans')
                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                        ->select('plans.id', 'plans.title', 'plans.group_id', 'plans.active' , 'groups.code as group_code')->where([
                            ['plans.hidden', 0]
                        ])->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

        }else{
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 29],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $plans = [];

                if($request->group_id !== null){
                    $facultyReq = DB::table('departments')
                        ->join('groups', 'groups.department_id', '=', 'departments.id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['groups.id', $request->group_id],
                        ])->first();
                    $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                        ['departments.id', $user->department_id],
                    ])->first();
                    foreach ($ret as $item){
                        if($item->type === 'faculty'){
                            if($item->scope === 'own'){
                                if(intval($faculty->faculty_id) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('plans')
                                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                            ['plans.group_id', $request->group_id],
                                            ['departments.faculty_id', intval($faculty->faculty_id)],
                                            ['departments.hidden', 0],
                                            ['plans.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($plans, $ret);
                                }
                            }else {
                                if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('plans')
                                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                            ['plans.group_id', $request->group_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['departments.hidden', 0],
                                            ['plans.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($plans, $ret);
                                }
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                if(intval($user->department_id) === intval($facultyReq->department_id)){
                                    $ret =  DB::table('plans')
                                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                            ['plans.group_id', $request->group_id],
                                            ['departments.id', intval($facultyReq->department_id)],
                                            ['departments.hidden', 0],
                                            ['plans.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($plans, $ret);
                                }
                            }else {
                                if(intval($item->scope) === intval($facultyReq->department_id)){
                                    $ret =  DB::table('plans')
                                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                            ['plans.group_id', $request->group_id],
                                            ['departments.id', intval($facultyReq->department_id)],
                                            ['departments.hidden', 0],
                                            ['plans.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($plans, $ret);
                                }
                            }
                        }
                    }
                }else {
                    $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                        ['departments.id', $user->department_id],
                    ])->first();
                    foreach ($ret as $item){
                        if($item->type === 'faculty'){
                            if($item->scope === 'own'){
                                $ret =  DB::table('plans')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                        ['departments.faculty_id', intval($faculty->faculty_id)],
                                        ['departments.hidden', 0],
                                        ['plans.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                array_push($plans, $ret);

                            }else {
                                $ret =  DB::table('plans')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('plans.id', 'plans.title', 'plans.group_id', 'plans.active' ,'groups.code as group_code')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['departments.hidden', 0],
                                        ['plans.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                array_push($plans, $ret);
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                $ret =  DB::table('plans')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                        ['departments.id', intval($user->department_id)],
                                        ['plans.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                array_push($plans, $ret);
                            }else {
                                $ret =  DB::table('plans')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('plans.id', 'plans.title', 'plans.group_id','plans.active' , 'groups.code as group_code')->where([
                                        ['groups.department_id', intval($item->scope)],
                                        ['plans.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                array_push($plans, $ret);
                            }
                        }
                    }
                }

                return response(json_encode(Normalize::normalize($plans), JSON_UNESCAPED_UNICODE), 200);
            } else {
                return response('forbidden', 403);
            }

        }



    }



    private  function create_plan($request){
        $date = date('Y-m-d H:i:s');
        $ret = Plan::create(
            [
                'title' => $request->title,
                'group_id' => $request->group_id,
                'created_at' => $date,
                'updated_at' => $date,
            ]
        );
        return $ret;
    }




    public function create(Request $request){
       //requests
       $err=[];
       if($request->header('token') === null){
           array_push($err, 'token is required');
       }
       if($request->title === null){
           array_push($err, 'title is required');
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




       if($user->id === 1){
           $ret = PlanController::create_plan($request);
           $date = date('Y-m-d H:i:s');
           try{
               Log::create([
                   'user_id' => $user->id,
                   'action' => 'Plan create',
                   'updated_at' => $date,
                   'created_at' => $date
               ]);
           }
           catch (Exception $e){
               return response($e, 500);
           }
           return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

       }else{
           try{
               $ret = DB::table('possibility_has_roles')
                   ->select()->where([
                       ['possibility_has_roles.role_id', $user->role_id],
                       ['possibility_has_roles.possibility_id', 30],
                       ['possibility_has_roles.hidden', 0]
                   ])->get();
           }
           catch (Exception $e){
               return response($e, 500);
           }
           if(count($ret)>0) {
               $flag = false;
               $facultyReq = DB::table('departments')
                   ->join('groups', 'groups.department_id', '=', 'departments.id')
                   ->select('departments.faculty_id')->where([
                   ['groups.id', $request->group_id],
               ])->first();
               $faculty = DB::table('departments')
                   ->select('departments.faculty_id')->where([
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
                   $ret = PlanController::create_plan($request);
                   $date = date('Y-m-d H:i:s');
                   try{
                       Log::create([
                           'user_id' => $user->id,
                           'action' => 'Plan create',
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
           }else{
               return response('forbidden', 403);
           }
       }
   }

   private function update_plan($request){
       $date = date('Y-m-d H:i:s');
       try {
           DB::table('plans')
               ->where('plans.id', $request->plan_id)
               ->update(
                   [
                       'title' => $request->title,
                       'group_id' => $request->group_id,
                       'active' => 0,
                       'updated_at' => $date,
                   ]
               );
       } catch (Exception $e) {
           return 'err';
       }
       try {
           $ret = DB::table('plans')
               ->select('plans.id', 'plans.title', 'plans.group_id')->where('plans.id', $request->plan_id)->first();
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
       if($request->title === null){
           array_push($err, 'title is required');
       }
       if($request->plan_id === null){
           array_push($err, 'plan_id is required');

       }else {
           try{
               $ret = DB::table('plans')
                   ->select('plans.id')->where([
                       ['plans.id', $request->plan_id],
                       ['plans.hidden', 0]
                   ])->first();
           }
           catch (Exception $e){
               return response($e, 500);
           }
           if($ret === null){
               array_push($err, 'plan must exist');
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
           $ret = PlanController::update_plan($request);
           if($ret === 'err'){
               return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
           }else{
               $date = date('Y-m-d H:i:s');
               try{
                   Log::create([
                       'user_id' => $user->id,
                       'action' => 'Plan update',
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
           try {
               $ret = DB::table('possibility_has_roles')
                   ->select()->where([
                       ['possibility_has_roles.role_id', $user->role_id],
                       ['possibility_has_roles.possibility_id', 31],
                       ['possibility_has_roles.hidden', 0]
                   ])->get();
           } catch (Exception $e) {
               return response($e, 500);
           }
           if (count($ret) > 0) {
               $flagFrom = false;
               $flagTo = false;



               $group = DB::table('groups')
                   ->join('departments', 'departments.id', 'groups.department_id')
                   ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.faculty_id')->where('groups.id', $request->group_id)->first();

               $planOld =  DB::table('plans')
                   ->join('groups', 'groups.id', 'plans.group_id')
                   ->join('departments', 'departments.id', 'groups.department_id')
                   ->select('plans.id',  'plans.group_id', 'groups.department_id', 'departments.faculty_id')->where('plans.id', $request->plan_id)->first();


               $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                   ['departments.id', $user->department_id],
               ])->first();

               foreach ($ret as $item) {
                   if ($item->type === 'faculty') {
                       if ($item->scope === 'own') {

                           if (intval($faculty->faculty_id) === intval($group->faculty_id)) {
                               $flagTo = true;
                           }
                           if (intval($faculty->faculty_id) === intval($planOld->faculty_id)) {
                               $flagFrom = true;
                           }
                           continue;
                       } else {
                           if (intval($item->scope) === intval($group->faculty_id)) {
                               $flagTo = true;
                           }
                           if (intval($item->scope) === intval($planOld->faculty_id)) {
                               $flagFrom = true;
                           }
                           continue;
                       }
                   } else if ($item->type === 'department') {
                       if ($item->scope === 'own') {
                           if (intval($user->department_id) === intval($group->department_id)) {
                               $flagTo = true;
                           }
                           if (intval($user->department_id) === intval($planOld->department_id)) {
                               $flagFrom = true;
                           }
                           continue;
                       } else {
                           if (intval($item->scope) === intval($group->department_id)) {
                               $flagTo = true;
                           }
                           if (intval($item->scope) === intval($planOld->department_id)) {
                               $flagFrom = true;
                           }
                           continue;
                       }
                   }
               }


               if ($flagFrom && $flagTo) {
                   $ret = PlanController::update_plan($request);
                   if ($ret === 'err') {
                       return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                   } else {
                       $date = date('Y-m-d H:i:s');
                       try{
                           Log::create([
                               'user_id' => $user->id,
                               'action' => 'Plan update',
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
                   return response('forbidden', 403);
               }
           } else {
               return response('forbidden', 403);
           }
       }
   }

   private  function delete_plan($request){
       $date = date('Y-m-d H:i:s');
       DB::beginTransaction();
       try{
           $ret = DB::table('choises')
               ->join('notes', 'notes.id', 'choises.subject_id')
               ->select('choises.id')
               ->where([
                   ['notes.plan_id', $request->plan_id],
                   ['choises.subject_type', 'N']
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
           $ret = DB::table('notes')
               ->join('lecturer_has_subjects', 'notes.id', 'lecturer_has_subjects.subject_id')
               ->select('lecturer_has_subjects.id')
               ->where([
                   ['notes.plan_id', $request->plan_id]
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
               ->join('subject_requirements', 'notes.id', 'subject_requirements.subject_id')
               ->select('subject_requirements.id')
               ->where([
                   ['notes.plan_id', $request->plan_id]
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
               ->select('notes.id')
               ->where([
                   ['notes.plan_id', $request->plan_id]
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
       try {
           Plan::destroy( $request->plan_id);
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
        if($request->plan_id === null){
            array_push($err, 'plan_id is required');

        }else {
            try{
                $ret = DB::table('plans')
                    ->select('plans.id')->where([
                        ['plans.id', $request->plan_id],
                        ['plans.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'plan must exist');
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
            $ret = PlanController::delete_plan($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Plan delete',
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
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 32],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {

                $flag= false;

                $plan =  DB::table('plans')
                    ->join('groups', 'groups.id', 'plans.group_id')
                    ->join('departments', 'departments.id', 'groups.department_id')
                    ->select('plans.id',  'plans.group_id', 'groups.department_id', 'departments.faculty_id')->where('plans.id', $request->plan_id)->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){

                            if(intval($faculty->faculty_id) === intval($plan->faculty_id)){
                                $flag = true;
                                break;
                            }

                        }else {
                            if(intval($item->scope) === intval($plan->faculty_id)){
                                $flag = true;
                                break;
                            }

                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($plan->department_id)){
                                $flag = true;
                                break;
                            }

                        }else {
                            if(intval($item->scope)  === intval($plan->department_id)){
                                $flag = true;
                                break;
                            }

                        }
                    }
                }
                if($flag){
                    $ret = PlanController::delete_plan($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Plan delete',
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



    private  function import_plane($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            $tmpPlan = Plan::create(
                [
                    'title' => $request->title,
                    'group_id' => $request->group_id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
        } catch (\Exception $e) {
            DB::rollback();
            return 'err';
        }


        $array = Excel::toArray(null, request()->file('file'));


        $weeksOnSemester = [];
        $j =0;
        for($i = 17; $i < 17+8; $i++){
            $weeksOnSemester[$j]= $array[1][8][$i];
            $j++;
        }

        $flag = 0;
        $subject_name = '';




        $ret = [];
        $ret['plan']=$tmpPlan;
        $ret['notes']=[];


        $counter = 0;
        foreach ($array[1] as $row){
            $counter++;

            if(strripos($row[0], 'Нормативні дисципліни') !== false){

                $flag = 1;
                continue;
            }
            if(strripos($row[0], 'Вибіркові дисципліни') !== false){
                $flag = 2;
                continue;
            }
            if(strripos($row[0], 'Всього') !== false){
                $flag = 0;
                continue;
            }
            if(strripos($row[0], 'Практична підготовка') !== false){
                break;
            }

            if($flag === 0){
                continue;
            }

            if($row[0]!== null){
                $subject_name = $row[2];
                if($array[1][$counter][0]!== null){
                    $semester = 0;
                    for($i = 17; $i < 17+8; $i++){
                        if($row[$i] !== null){
                            $semester = $i-16;
                        }
                    }


                    $inp =   [
                        'plan_id' => $tmpPlan->id,
                        'subject_name' => $subject_name,
                        'semester' => $semester,
                        'weeks_in_semester' => $weeksOnSemester[$semester-1],
                        'par_per_week' => $row[$semester-1 + 17],
                        'credits_ECTS' => $row[17+9+$semester-1],
                        'created_at' => $date,
                        'updated_at' => $date
                    ];


                    if($row[6] !== null){
                        $inp['zalic_or_examen']= 'E';
                        $inp['z_or_e_number']= $row[6];
                    }
                    if($row[7] !== null){
                        $inp['zalic_or_examen']= 'Z';
                        $inp['z_or_e_number']= $row[7];
                    }

                    if($row[8] !== null){
                        $inp['cours_projects']= $row[8] ;
                    }
                    if($row[9] !== null){
                        $inp['cours_work']= $row[9] ;
                    }

                    if($row[13] !== null){
                        $inp['leccii']= $row[13] ;
                    }
                    if($row[14] !== null){
                        $inp['laborat']= $row[14] ;
                    }
                    if($row[15] !== null){
                        $inp['practik']= $row[15] ;
                    }
                    if($row[16] !== null){
                        $inp['samostiyna_robta']= $row[16] ;
                    }

                    if( $flag == 1){
                        $inp['type']= 'N';
                    }
                    if( $flag == 2){
                        $inp['type']= 'V';
                    }





                    try {
                        $tmp = Note::create($inp);
                    } catch (\Exception $e) {
                        DB::rollback();
                        return 'err';
                    }
                    array_push( $ret['notes'], $tmp);
                }
                continue;
            }

            $semester = 0;
            for($i = 17; $i < 17+8; $i++){
                if($row[$i] !== null){
                    $semester = $i-16;
                }
            }


            $inp =   [
                'plan_id' => $tmpPlan->id,
                'subject_name' => $subject_name,
                'semester' => $semester,
                'weeks_in_semester' => $weeksOnSemester[$semester-1],
                'par_per_week' => $row[$semester-1 + 17],
                'credits_ECTS' => $row[17+9+$semester-1],
                'created_at' => $date,
                'updated_at' => $date
            ];


            if($row[6] !== null){
                $inp['zalic_or_examen']= 'E';
                $inp['z_or_e_number']= $row[6];
            }
            if($row[7] !== null){
                $inp['zalic_or_examen']= 'Z';
                $inp['z_or_e_number']= $row[7];
            }

            if($row[8] !== null){
                $inp['cours_projects']= $row[8] ;
            }
            if($row[9] !== null){
                $inp['cours_work']= $row[9] ;
            }

            if($row[13] !== null){
                $inp['leccii']= $row[13] ;
            }
            if($row[14] !== null){
                $inp['laborat']= $row[14] ;
            }
            if($row[15] !== null){
                $inp['practik']= $row[15] ;
            }
            if($row[16] !== null){
                $inp['samostiyna_robta']= $row[16] ;
            }

            if( $flag == 1){
                $inp['type']= 'N';
            }
            if( $flag == 2){
                $inp['type']= 'V';
            }





            try {
                $tmp = Note::create($inp);
            } catch (\Exception $e) {
                DB::rollback();
                return 'err';
            }
            array_push( $ret['notes'], $tmp);
        }
        DB::commit();
        return $ret;

    }

    public function import(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->title === null){
            array_push($err, 'title is required');
        }
        if($request->file('file') === null){
            array_push($err, 'file is required');
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
            $ret = PlanController::import_plane($request);
            if($ret !== 'err'){
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Plan import',
                        'updated_at' => $date,
                        'created_at' => $date
                    ]);
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }else {
                return response('server error', 500);
            }

        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 30],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->orWhere([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 42],
                        ['possibility_has_roles.hidden', 0]
                     ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {

                $flag1 = false;
                $flag2 = false;

                $reqFaculty = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('departments.faculty_id', 'groups.department_id')->where([
                        ['groups.id', $request->group_id],
                    ])
                    ->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {

                            if (intval($faculty->faculty_id) === intval($reqFaculty->faculty_id)) {
                                if(intval($item->possibility_id) === 30){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 42){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqFaculty->faculty_id)) {
                                if(intval($item->possibility_id) === 30){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 42){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqFaculty->department_id)) {
                                if(intval($item->possibility_id) === 30){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 42){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqFaculty->department_id)) {
                                if(intval($item->possibility_id) === 30){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 42){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        }
                    }
                }
                if ($flag1 && $flag2) {
                    $ret = PlanController::import_plane($request);
                    if($ret !== 'err'){
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Plan import',
                                'updated_at' => $date,
                                'created_at' => $date
                            ]);
                        }
                        catch (Exception $e){
                            return response($e, 500);
                        }
                        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                    }else {
                        return response('server error', 500);
                    }
                } else {
                    return response('forbidden', 403);
                }

            } else {
                return response('forbidden', 403);
            }
        }
    }
    public function getByID(Request $request, $id){
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
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

        if($user->id === 1){
            $tmp = [];
            try {
                $ret = DB::table('plans')
                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                    ->select('plans.id', 'plans.title', 'plans.group_id', 'groups.code as group_code')->where([
                        ['plans.id', $id],
                        ['plans.hidden', 0]
                    ])->get();
            }catch (Exception $e){
                return response($e, 500);
            }
            try {
                $ret2 = DB::table('notes')
                    ->join('plans', 'plans.id', '=', 'notes.plan_id')
                    ->select('notes.id', 'notes.credits_ECTS', 'notes.type','notes.semester','notes.zalic_or_examen','notes.z_or_e_number','notes.cours_projects', 'notes.cours_work','notes.leccii', 'notes.laborat','notes.practik', 'notes.samostiyna_robta','notes.weeks_in_semester','notes.par_per_week','notes.subject_name', 'notes.plan_id', 'plans.title as plan_title')->where([
                        ['notes.plan_id', $id],
                        ['notes.hidden', 0]
                    ])->get();
            }catch (Exception $e){
                return response($e, 500);
            }
            $tmp['plan']=$ret;
            $tmp['notes']= $ret2;


            return response(json_encode($tmp, JSON_UNESCAPED_UNICODE), 200);

        }else{
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 29],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->orWhere([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 41],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $tmp = [];
                try {
                    $ret3 = DB::table('plans')
                        ->join('groups', 'groups.id', '=', 'plans.group_id')
                        ->select('plans.id', 'plans.title', 'plans.group_id', 'groups.code as group_code')->where([
                            ['plans.id', $id],
                            ['plans.hidden', 0]
                        ])->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
                try {
                    $ret4 = DB::table('notes')
                        ->join('plans', 'plans.id', '=', 'notes.plan_id')
                        ->select('notes.id', 'notes.type','notes.semester','notes.zalic_or_examen','notes.z_or_e_number','notes.cours_projects', 'notes.cours_work','notes.leccii', 'notes.laborat','notes.practik', 'notes.samostiyna_robta','notes.weeks_in_semester','notes.par_per_week','notes.subject_name', 'notes.plan_id', 'plans.title as plan_title')->where([
                            ['notes.plan_id', $id],
                            ['notes.hidden', 0]
                        ])->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
                $tmp['plan']=$ret3;
                $tmp['notes']= $ret4;


                $reqFaculty = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('departments.faculty_id', 'groups.department_id')->where([
                        ['groups.id',  $tmp['plan'][0]->group_id],
                    ])
                    ->first();


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                $flag1 = false;
                $flag2 = false;
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {

                            if (intval($faculty->faculty_id) === intval($reqFaculty->faculty_id)) {
                                if(intval($item->possibility_id) === 29){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 41){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqFaculty->faculty_id)) {
                                if(intval($item->possibility_id) === 29){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 41){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqFaculty->department_id)) {
                                if(intval($item->possibility_id) === 29){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 41){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqFaculty->department_id)) {
                                if(intval($item->possibility_id) === 29){
                                    $flag1 = true;
                                }elseif (intval($item->possibility_id) === 41){
                                    $flag2 = true;
                                }

                                continue;
                            }

                        }
                    }
                }
                if ($flag1 && $flag2) {
                    return response(json_encode($tmp, JSON_UNESCAPED_UNICODE), 200);
                } else {
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private function set_active($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            $plan = DB::table('plans')
                ->select('plans.id', 'plans.group_id')->where([
                    ['plans.id', $request->plan_id],
                    ['plans.hidden', 0]
                ])->first();
        } catch (\Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('plans')
                ->where('plans.group_id', $plan->group_id)
                ->update(
                    [
                        'active' => false,
                        'updated_at' => $date,
                    ]
                );
        } catch (\Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('plans')
                ->where('plans.id',  $request->plan_id)
                ->update(
                    [
                        'active' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (\Exception $e) {
            DB::rollback();
            return 'err';
        }

        DB::commit();
        return 'OK';
    }


    public function setActive(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->plan_id === null){
            array_push($err, 'plan_id is required');

        }else {
            try{
                $ret = DB::table('plans')
                    ->select('plans.id')->where([
                        ['plans.id', $request->plan_id],
                        ['plans.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'plan must exist');
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
            $ret = PlanController::set_active($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Plan set active',
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
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 31],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flagFrom = false;

                $planOld =  DB::table('plans')
                    ->join('groups', 'groups.id', 'plans.group_id')
                    ->join('departments', 'departments.id', 'groups.department_id')
                    ->select('plans.id',  'plans.group_id', 'groups.department_id', 'departments.faculty_id')->where('plans.id', $request->plan_id)->first();


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {

                            if (intval($faculty->faculty_id) === intval($planOld->faculty_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($planOld->faculty_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($planOld->department_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($planOld->department_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    }
                }


                if ($flagFrom) {
                    $ret = PlanController::set_active($request);
                    if ($ret === 'err') {
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    } else {
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Plan set active',
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
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


}
