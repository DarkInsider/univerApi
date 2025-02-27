<?php

namespace App\Http\Controllers;

use App\Choise;
use App\Log;
use App\Student;
use Illuminate\Http\Request;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;
use App\Http\Helpers\ExportStudents;
use App\User;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{

    public function getOwnGroups(Request $request){
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

        try {
            $students = DB::table('students')
                ->join('groups', 'students.group_id','groups.id')
                ->select('students.id as student_id', 'students.group_id', 'groups.code')->where([
                    ['students.user_id', $user->id],
                    ['students.hidden', 0]
                ])->get();
        } catch (Exception $e) {
            return response($e, 500);
        }
		
		
		foreach($students as $student){
			 try {
				$plan = DB::table('plans')
					->select('plans.id as plan_id')->where([
						['plans.group_id', $student->group_id],
						['plans.hidden', 0],
						['plans.active', 1]
					])->first();
			} catch (Exception $e) {
				return response($e, 500);
			}
			if($plan != null){
				$student->plan_id=$plan->plan_id;
			}else{
				$student->plan_id='plan_error';
			}
		}
		
	

        return response(  json_encode($students, JSON_UNESCAPED_UNICODE), 200);

    }


    public function studentExport(Request $request){
        //requests
        $err=[];
        if($request->token === null){
            array_push($err, 'token is required');
        }
        if($request->group_id === null){
            array_push($err, 'group_id is required');
        } else {
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
        $user = GetUser::get($request->token);
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }

        if($user->id === 1){
            return Excel::download(new ExportStudents($request->group_id), 'students_'.$request->group_id.'.xlsx');
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 21],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag = false;
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            if(intval($faculty->faculty_id) === intval($req->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            if(intval($item->scope) === intval($req->faculty_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($req->department_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            if(intval($item->scope) === intval($req->department_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if($flag){
                    return Excel::download(new ExportStudents($request->group_id), 'students_'.$request->group_id.'.xlsx');
                }else{
                    return response('forbidden', 403);
                }
            }  else{
                return response('forbidden', 403);
            }
        }
    }
   public function get(Request $request){
       //requests
       $err=[];
       if($request->header('token') === null){
           array_push($err, 'token is required');
       }
       if($request->group_id === null){
           array_push($err, 'group_id is required');
       } else {
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
           try {
               $ret2 = DB::table('groups')
                   ->join('plans', 'groups.id', '=', 'plans.group_id')
                   ->select()->where([
                       ['groups.id', $request->group_id],
                       ['plans.active', 1],
                       ['plans.hidden', 0]
                   ])->first();
           } catch (Exception $e) {
               return response($e, 500);
           }

           if($ret2 != null){
               try {
                   $ret = DB::table('students')
                       ->join('groups', 'groups.id', '=', 'students.group_id')
                       ->join('plans', 'groups.id', '=', 'plans.group_id')
                       ->join('users', 'users.id', '=', 'students.user_id')
                       ->select('students.id', 'students.info', 'students.group_id', 'groups.semester', 'plans.id as plan_id', 'students.user_id', 'users.name', 'groups.code as group_code')->where([
                           ['students.group_id', $request->group_id],
                           ['plans.active', 1],
                           ['students.hidden', 0]
                       ])->get();
               } catch (Exception $e) {
                   return response($e, 500);
               }

//               foreach ($ret as $item){
//                   try {
//                       $tmp = DB::table('notes')
//                           ->select(DB::raw('SUM(credits_ECTS) as total_credits_ECTS'))->where([
//                               ['notes.plan_id', $ret->plan_id],
//                               ['notes.semester', $ret->semester],
//                               ['notes.type', 'V'],
//                               ['notes.hidden', 0]
//                           ])
//                           ->groupBy('semester')
//                           ->first();
//                   } catch (Exception $e) {
//                       return response($e, 500);
//                   }
//                   $item->hours=$tmp->total_hours;
//
//                   try {
//                       $tmp = DB::table('choises')
//                           ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
//                           ->select( DB::raw('SUM(subjects.hours) as total_hours'))->where([
//                               ['choises.student_id', $item->id],
//                               ['choises.hidden', 0]
//                           ])
//                           ->groupBy('choises.student_id')
//                           ->first();
//                   } catch (Exception $e) {
//                       return response($e, 500);
//                   }
//                   if($tmp !== null){
//                       $item->hours_selected=$tmp->total_hours;
//                   }else {
//                       $item->hours_selected=0;
//                   }
//
//               }




           }else {
               try {
                   $ret = DB::table('students')
                       ->join('groups', 'groups.id', '=', 'students.group_id')
                       ->join('users', 'users.id', '=', 'students.user_id')
                       ->select('students.id', 'students.info', 'students.group_id',  'students.user_id', 'users.name', 'groups.code as group_code')->where([
                           ['students.group_id', $request->group_id],
                           ['students.hidden', 0]
                       ])->get();
               } catch (Exception $e) {
                   return response($e, 500);
               }
           }
           return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

       }else {
           try {
               $ret = DB::table('possibility_has_roles')
                   ->select()->where([
                       ['possibility_has_roles.role_id', $user->role_id],
                       ['possibility_has_roles.possibility_id', 21],
                       ['possibility_has_roles.hidden', 0]
                   ])->get();
           } catch (Exception $e) {
               return response($e, 500);
           }
           if(count($ret)>0) {
               $flag = false;
               $req = DB::table('groups')
                   ->join('departments', 'departments.id', '=', 'groups.department_id')
                   ->select('groups.department_id', 'departments.faculty_id')->where([
                       ['groups.id', $request->group_id],
                       ['groups.hidden', 0]
                   ])->first();
               $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                   ['departments.id', $user->department_id],
               ])->first();
               foreach ($ret as $item){
                   if($item->type === 'faculty'){
                       if($item->scope === 'own'){
                           if(intval($faculty->faculty_id) === intval($req->faculty_id)){
                               $flag = true;
                               break;
                           }
                       }else {
                           if(intval($item->scope) === intval($req->faculty_id)){
                               $flag = true;
                               break;
                           }
                       }
                   }else if($item->type === 'department'){
                       if($item->scope === 'own'){
                           if(intval($user->department_id) === intval($req->department_id)){
                               $flag = true;
                               break;
                           }
                       }else {
                           if(intval($item->scope) === intval($req->department_id)){
                               $flag = true;
                               break;
                           }
                       }
                   }
               }
               if($flag){
                   try {
                       $ret2 = DB::table('groups')
                           ->join('plans', 'groups.id', '=', 'plans.group_id')
                           ->select()->where([
                               ['groups.id', $request->group_id],
                               ['plans.active', 1],
                               ['plans.hidden', 0]
                           ])->first();
                   } catch (Exception $e) {
                       return response($e, 500);
                   }

                   if($ret2 != null){
                       try {
                           $ret = DB::table('students')
                               ->join('groups', 'groups.id', '=', 'students.group_id')
                               ->join('plans', 'groups.id', '=', 'plans.group_id')
                               ->join('users', 'users.id', '=', 'students.user_id')
                               ->select('students.id', 'students.info', 'students.group_id', 'groups.semester', 'plans.id as plan_id', 'students.user_id', 'users.name', 'groups.code as group_code')->where([
                                   ['students.group_id', $request->group_id],
                                   ['plans.active', 1],
                                   ['students.hidden', 0]
                               ])->get();
                       } catch (Exception $e) {
                           return response($e, 500);
                       }

//                       foreach ($ret as $item){
//                           try {
//                               $tmp = DB::table('notes')
//                                   ->select( DB::raw('SUM(hours) as total_hours'))->where([
//                                       ['notes.plan_id', $item->plan_id],
//                                       ['notes.semester',  $item->semester],
//                                       ['notes.hidden', 0]
//                                   ])
//                                   ->groupBy('semester')
//                                   ->first();
//                           } catch (Exception $e) {
//                               return response($e, 500);
//                           }
//                           $item->hours=$tmp->total_hours;
//
//                           try {
//                               $tmp = DB::table('choises')
//                                   ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
//                                   ->select( DB::raw('SUM(subjects.hours) as total_hours'))->where([
//                                       ['choises.student_id', $item->id],
//                                       ['choises.hidden', 0]
//                                   ])
//                                   ->groupBy('choises.student_id')
//                                   ->first();
//                           } catch (Exception $e) {
//                               return response($e, 500);
//                           }
//                           if($tmp !== null){
//                               $item->hours_selected=$tmp->total_hours;
//                           }else {
//                               $item->hours_selected=0;
//                           }
//
//                       }




                   }else {
                       try {
                           $ret = DB::table('students')
                               ->join('groups', 'groups.id', '=', 'students.group_id')
                               ->join('users', 'users.id', '=', 'students.user_id')
                               ->select('students.id', 'students.info', 'students.group_id',  'students.user_id', 'users.name', 'groups.code as group_code')->where([
                                   ['students.group_id', $request->group_id],
                                   ['students.hidden', 0]
                               ])->get();
                       } catch (Exception $e) {
                           return response($e, 500);
                       }
                   }


                   return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
               }else{
                   return response('forbidden', 403);
               }
           }  else{
               return response('forbidden', 403);
           }
       }
   }

   private   function createStudent($request){
       $date = date('Y-m-d H:i:s');
       $ret = Student::create(
           [
               'info' => $request->info,
               'group_id' => $request->group_id,
               'user_id' => $request->user_id,
               'created_at' => $date,
               'updated_at' => $date,
           ]
       );
       return $ret;
   }

   private  function create_user($request){
       $date = date('Y-m-d H:i:s');
       $ret = User::create(
           [
               'name' => $request->name,
               'login' => $request->login,
               'password' => md5($request->password),
               'role_id' => $request->role_id,
               'department_id' => $request->department_id,
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
        if ($request->info === null) {
            array_push($err, 'info is required');
        }
        if ($request->flag === null) {
            array_push($err, 'flag is required  (select existing user (1), or create new (0)');
        }else{
            if (intval($request->flag) === 1) {
                if ($request->user_id === null) {
                    array_push($err, 'user_id is required');
                }else{
                    try {
                        $ret = DB::table('users')
                            ->select('users.id')->where([
                                ['users.id', $request->user_id],
                                ['users.hidden', 0]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if ($ret === null) {
                        array_push($err, 'user must exist');
                    }
                }
            }else {
                if($request->name === null){
                    array_push($err, 'name is required');
                }
                if($request->login === null){
                    array_push($err, 'login is required');

                }else {
                    try{
                        $user = DB::table('users')
                            ->select('users.login')->where('users.login', $request->login)->first();
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }
                    if($user !== null){
                        array_push($err, 'login must be unique');
                    }
                }
                if($request->password === null){
                    array_push($err, 'password is required');
                }
            }
        }
        if ($request->group_id === null) {
            array_push($err, 'group_id is required');
        } else {
            try {
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'group must exist');
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






        if($user->id === 1){

            if (intval($request->flag) === 1) {
                $ret = StudentController::createStudent((object)array(
                    'info' => $request->info,
                    'group_id' => $request->group_id,
                    'user_id' => $request->user_id,
                ));
            }else{
                DB::beginTransaction();
                try {
                    $group = DB::table('groups')
                        ->select('groups.id', 'groups.department_id')->where([
                            ['groups.id', $request->group_id],
                            ['groups.hidden', 0]
                        ])->first();
                } catch (Exception $e) {
                    DB::rollback();
                    return response($e, 500);
                }

                try {
                    $newUser = StudentController::create_user((object)array(
                        'name' => $request->name,
                        'login' => $request->login,
                        'password' => md5($request->password),
                        'role_id' => 4,
                        'department_id' => $group->department_id,
                    ));
                }catch (Exception $e) {
                    DB::rollback();
                    return response($e, 500);
                }
                try {
                    $ret = StudentController::createStudent((object)array(
                        'info' => $request->info,
                        'group_id' => $request->group_id,
                        'user_id' => $newUser->id,
                    ));
                }catch (Exception $e) {
                    DB::rollback();
                    return response($e, 500);
                }
                DB::commit();
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 22],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->orWhere([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 14],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            if(intval($faculty->faculty_id) === intval($req->faculty_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }else {
                            if(intval($item->scope) === intval($req->faculty_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($req->department_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }else {
                            if(intval($item->scope) === intval($req->department_id)){
                                if (intval($item->possibility_id) === 22){
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14){
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    }
                }
                if($flag1 && $flag2){
                    if (intval($request->flag) === 1) {
                        $ret = StudentController::createStudent((object)array(
                            'info' => $request->info,
                            'group_id' => $request->group_id,
                            'user_id' => $request->user_id,
                        ));
                    }else{
                        DB::beginTransaction();
                        try {
                            $group = DB::table('groups')
                                ->select('groups.id', 'groups.department_id')->where([
                                    ['groups.id', $request->group_id],
                                    ['groups.hidden', 0]
                                ])->first();
                        } catch (Exception $e) {
                            DB::rollback();
                            return response($e, 500);
                        }

                        try {
                            $newUser = StudentController::create_user((object)array(
                                'name' => $request->name,
                                'login' => $request->login,
                                'password' => md5($request->password),
                                'role_id' => 4,
                                'department_id' => $group->department_id,
                            ));
                        }catch (Exception $e) {
                            DB::rollback();
                            return response($e, 500);
                        }
                        try {
                            $ret = StudentController::createStudent((object)array(
                                'info' => $request->info,
                                'group_id' => $request->group_id,
                                'user_id' => $newUser->id,
                            ));
                        }catch (Exception $e) {
                            DB::rollback();
                            return response($e, 500);
                        }
                        DB::commit();
                    }


                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Student create',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }

    private  function update_student($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('students')
                ->where('students.id', $request->student_id)
                ->update(
                    [
                        'info' => $request->info,
                        'group_id' => $request->group_id,
                        'user_id' => $request->user_id,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            DB::table('users')
                ->where('users.id', $request->user_id)
                ->update(
                    [
                        'name' => $request->name,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        try {
            $ret = DB::table('students')
                ->select('students.id', 'students.info', 'students.group_id', 'students.user_id')->where('students.id', $request->student_id)->first();
        } catch (Exception $e) {
            DB::rollback();
            return 'err';
        }
        DB::commit();
        return $ret;
    }

    public function update(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $ret = DB::table('students')
                    ->select('students.id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'student must exist');
            }
        }
        if ($request->info === null) {
            array_push($err, 'info is required');
        }
//         if ($request->semester === null) {
//             array_push($err, 'semester is required');
//         }
        if ($request->name === null) {
            array_push($err, 'name is required');
        }
        if ($request->user_id === null) {
            array_push($err, 'user_id is required');
        }else{
            try {
                $ret = DB::table('users')
                    ->select('users.id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'user must exist');
            }
        }
        if ($request->group_id === null) {
            array_push($err, 'group_id is required');
        } else {
            try {
                $ret = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'group must exist');
            }
        }
        if (count($err) > 0) {
            return response($err, 400);
        }


//        try{
//            $ret = DB::table('students')
//                ->join('groups', 'students.group_id', 'groups.id')
//                ->join('plans', 'plans.group_id', 'groups.id')
//                ->join('notes', 'plans.id', 'notes.plan_id')
//                ->select()
//                ->where([
//                    ['plans.active', 1],
//                    ['students.id', $request->student_id],
//                    ['notes.semester', $request->semester]
//                ])
//                ->first();
//        }catch (Exception $e){
//            return response($e, 500);
//        }
//
//        if ($ret === null) {
//            array_push($err, 'semester must exist');
//        }

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


        if($user->id === 1){
            $ret = StudentController::update_student($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Student update',
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
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 23],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $studOld= DB::table('students')
                    ->join('groups', 'groups.id', '=', 'students.group_id')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($faculty->faculty_id) === intval($studOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($studOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($user->department_id) === intval($studOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($studOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    }
                }

                if($flag2 && $flag1){
                    $ret = StudentController::update_student($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Student update',
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

    function delete_student($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            DB::table('students')
                ->where('students.id', $request->student_id)
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

                ->select('choises.id')
                ->where([
                    ['choises.student_id', $request->student_id]
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


        DB::commit();
        return 'Delete OK';
    }

    public function delete(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $ret = DB::table('students')
                    ->select('students.id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'student must exist');
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



        if($user->id === 1){
            $ret = StudentController::delete_student($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Student delete',
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
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 24],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $req = DB::table('students')
                    ->join('groups', 'groups.id', '=', 'students.group_id')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                $flag1 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                $flag1 = true;
                            }
                            continue;
                        }
                    }
                }

                if($flag1){
                    $ret = StudentController::delete_student($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'Student delete',
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





    private function import_students($request){
        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        $array = Excel::toArray(null, request()->file('file'));
        $ret=[];
        try{
            $group = DB::table('groups')
                ->select('groups.id', 'groups.department_id')->where([
                    ['groups.id', $request->group_id],
                    ['groups.hidden', 0]
                ])->first();
        }
        catch (Exception $e){
            DB::rollback();
            return response($e, 500);
        }
        $loginsDublicat = [];
        foreach ($array[0] as $item){
            try {
                $login = DB::table('users')
                    ->select('users.login')->where([
                        ['users.login', $item[1]],
                    ])->first();
                if($login !== null){
                    array_push( $loginsDublicat, $login);
                }
            } catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }
        }
        if(count($loginsDublicat) > 0){
            DB::commit();
            $response = [];
            $response['code'] = 400;
            $response['message'] = 'login must be uniq';
            $response['data'] = $loginsDublicat;
            return $response;
        }

        foreach ($array[0] as $item){
            try{
                $newUser = User::create(
                    [
                        'name' => $item[0],
                        'login' => $item[1],
                        'password' => md5($item[2]),
                        'role_id' => 4,
                        'department_id' => $group->department_id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }
            try{
                $tmp = Student::create(
                    [
                       // 'info' => $item[3],
                        'group_id' => $request->group_id,
                        'user_id' => $newUser->id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }
            array_push( $ret, $tmp);
        }
        DB::commit();
        $response = [];
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $ret;
        return $response;
    }


    public function import(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
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
            }else {
                try{
                    $ret = DB::table('students')
                        ->select('students.id')->where([
                            ['students.group_id', $request->group_id],
                            ['students.hidden', 0]
                        ])->get();
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                if(count($ret) > 0) {
                    array_push($err, 'group must be empty');
                }
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
            $ret = StudentController::import_students($request);
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Student import',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 22],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->orWhere([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 14],
                        ['possibility_has_roles.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag1 = false;
                $flag2 = false;
                $req = DB::table('groups')
                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                    ->select('groups.department_id', 'departments.faculty_id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 22) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->faculty_id)) {
                                if (intval($item->possibility_id) === 22) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 22) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        } else {
                            if (intval($item->scope) === intval($req->department_id)) {
                                if (intval($item->possibility_id) === 22) {
                                    $flag1 = true;
                                }
                                if (intval($item->possibility_id) === 14) {
                                    $flag2 = true;
                                }
                                continue;
                            }
                        }
                    }
                }
                if ($flag1 && $flag2) {
                    $ret = StudentController::import_students($request);
                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Student import',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                }else{
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }
}
