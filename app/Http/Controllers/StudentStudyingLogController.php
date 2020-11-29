<?php
namespace App\Http\Controllers;

use App\Choise;
use App\Group;
use App\Log;
use App\Note;
use App\Students_studying_log;
use Illuminate\Http\Request;
use App\Http\Helpers\GetUser;
use Illuminate\Support\Facades\DB;

class StudentStudyingLogController extends Controller
{
    public function setGroupNextSemester(Request $request){
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
                    ->select('groups.id', 'groups.semester')->where([
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


        try{
            $need = DB::table('notes')
                ->join('plans', 'plans.id', 'notes.plan_id')
                ->selectRaw('sum(credits_ECTS) as sum')
                ->where([
                    ['notes.type', 'V'],
                    ['notes.semester', $ret->semester],
                    ['plans.hidden', 0],
                    ['plans.active', 1],
                    ['plans.group_id', $request->group_id]
                ])
                ->groupBy('notes.plan_id')
                ->get();
        }
        catch (Exception $e){
            return response($e, 500);
        }

        try{
            $cnt = DB::table('students')
                ->selectRaw('count(id) as sum')
                ->where([
                    ['students.hidden', 0],
                    ['students.group_id', $request->group_id]
                ])
                ->groupBy('group_id')
                ->get();
        }
        catch (Exception $e){
            return response($e, 500);
        }

        if((count($need) > 0) && (count($cnt) > 0)){
            $hours_need = $need[0]->sum * $cnt[0]->sum;
        }else{
            $hours_need =0;
        }


        try{
            $haveV = DB::table('choises')
                ->join('subjects', 'subjects.id', 'choises.subject_id')
                ->join('students', 'students.id', 'choises.student_id')
                ->selectRaw('sum(credits_ECTS) as sum')
                ->where([
                    ['choises.subject_type', 'V'],
                   // ['choises.semester', $ret->semester],
                    ['choises.hidden', 0],
                    ['students.group_id', $request->group_id]
                ])
                ->groupBy('choises.semester')
                ->get();
        }
        catch (Exception $e){
            return response($e, 500);
        }

        try{
            $haveN = DB::table('choises')
                ->join('notes', 'notes.id', 'choises.subject_id')
                ->join('students', 'students.id', 'choises.student_id')
                ->selectRaw('sum(notes.credits_ECTS) as sum')
                ->where([
                    ['choises.subject_type', 'N'],
                    // ['choises.semester', $ret->semester],
                    ['choises.hidden', 0],
                    ['students.group_id', $request->group_id]
                ])
                ->groupBy('choises.semester')
                ->get();
        }
        catch (Exception $e){
            return response($e, 500);
        }
        $have =0;
        if((count($haveN) > 0) ) {
            $have = $haveN[0]->sum;
        }
        if( (count($haveV) > 0)) {
            $have +=   $haveV[0]->sum;
        }
       // return response(json_encode($have - $hours_need, JSON_UNESCAPED_UNICODE), 200);
        if(($have - $hours_need) != 0){
            array_push($err, 'not all students from group make choice');
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
            DB::beginTransaction();
            try{
                $students = DB::table('students')
                    ->select()->where([
                        ['students.group_id', $request->group_id],
                        ['students.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }

            try{
                $group = DB::table('groups')
                    ->select()->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }


            try{
                $subjects = DB::table('notes')
                    ->join('plans', 'plans.id', '=', 'notes.plan_id')
                    ->select('subject_name','credits_ECTS','difficult')->where([
                        ['plans.group_id', $request->group_id],
                        ['notes.semester', $group->semester],
                        ['notes.type', "N"],
                        ['plans.active', 1]
                    ])->get();
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');

            foreach ($students as $student){

                try{
                    $vSubjects1 = DB::table('choises')
                        ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                        ->select('choises.id','subjects.title as subject_name','subjects.credits_ECTS','subjects.difficult')->where([
                            ['choises.subject_type', 'V'],
                            ['choises.student_id', $student->id],
                            ['choises.hidden', 0]
                        ])->get();
                }
                catch (Exception $e){
                    DB::rollback();
                    return response($e, 500);
                }
                try{
                    $vSubjects2 = DB::table('choises')
                        ->join('notes', 'notes.id', '=', 'choises.subject_id')
                        ->select('choises.id','notes.subject_name','notes.credits_ECTS','notes.difficult')->where([
                            ['choises.subject_type', 'N'],
                            ['choises.student_id', $student->id],
                            ['choises.hidden', 0]
                        ])->get();
                }
                catch (Exception $e){
                    DB::rollback();
                    return response($e, 500);
                }

                foreach ($vSubjects1 as $subject){
                    try{
                        Students_studying_log::create([
                            'student_id' => $student->id,
                            'university' => "L",
                            'date' => $date,
                            'group_name' => $group->code,
                            'difficult' => $subject->difficult,
                            'credits_ECTS' => $subject->credits_ECTS,
                            'semester' => $group->semester,
                            'subject_title' => $subject->subject_name,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]);
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }

                    try{

                        $ch = Choise::find($subject->id);

                        $ch->hidden = 1;
                        $ch->updated_at = $date;
                        $ch->save();

                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }
                }

                foreach ($vSubjects2 as $subject){
                    try{
                        Students_studying_log::create([
                            'student_id' => $student->id,
                            'university' => "L",
                            'date' => $date,
                            'group_name' => $group->code,
                            'difficult' => $subject->difficult,
                            'credits_ECTS' => $subject->credits_ECTS,
                            'semester' => $group->semester,
                            'subject_title' => $subject->subject_name,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]);
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }

                    try{

                        $ch = Choise::find($subject->id);

                        $ch->hidden = 1;
                        $ch->updated_at = $date;
                        $ch->save();

                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }
                }


                foreach ($subjects as $subject){
                    try{
                        Students_studying_log::create([
                            'student_id' => $student->id,
                            'university' => "L",
                            'date' => $date,
                            'group_name' => $group->code,
                            'difficult' => $subject->difficult,
                            'credits_ECTS' => $subject->credits_ECTS,
                            'semester' => $group->semester,
                            'subject_title' => $subject->subject_name,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]);
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }

                }
            }

            try{
                $maxSemester = DB::table('notes')
                    ->join('plans', 'plans.id', '=', 'notes.plan_id')
                    ->where([
                        ['plans.group_id', $request->group_id],
                        ['notes.type', "N"],
                        ['plans.active', 1]
                    ])
                    ->max('notes.semester');
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }



            try{
                $group = Group::find ($request->group_id);
                if($group->semester < $maxSemester){
                    $group->semester =  $group->semester + 1;
                }else{
                    $group->semester =  100;
                }

                $group->updated_at = $date;

                $group->save();
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }



            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Set group next semester',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                DB::rollback();
                return response($e, 500);
            }


            DB::commit();



            return response(json_encode("OK", JSON_UNESCAPED_UNICODE), 200);

        }else {
            try {
                $retaaa = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 27],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($retaaa) > 0) {
                try{
                    $reqParam = DB::table('groups')
                        ->join('departments', 'departments.id','groups.department_id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['groups.id', $request->group_id],
                            ['groups.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }

                $flagRND = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                    ['departments.hidden', 0]
                ])->first();



                foreach ($retaaa as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flagRND = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flagRND = true;
                                break;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flagRND = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flagRND = true;
                                break;
                            }

                        }
                    }
                }


                if($flagRND === true){
                    DB::beginTransaction();
                    try{
                        $students = DB::table('students')
                            ->select()->where([
                                ['students.group_id', $request->group_id],
                                ['students.hidden', 0]
                            ])->get();
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }

                    try{
                        $group = DB::table('groups')
                            ->select()->where([
                                ['groups.id', $request->group_id],
                                ['groups.hidden', 0]
                            ])->first();
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }


                    try{
                        $subjects = DB::table('notes')
                            ->join('plans', 'plans.id', '=', 'notes.plan_id')
                            ->select('subject_name','credits_ECTS','difficult')->where([
                                ['plans.group_id', $request->group_id],
                                ['notes.semester', $group->semester],
                                ['notes.type', "N"],
                                ['plans.active', 1]
                            ])->get();
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }

                    $date = date('Y-m-d H:i:s');

                    foreach ($students as $student){

                        try{
                            $vSubjects1 = DB::table('choises')
                                ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
                                ->select('choises.id','subjects.title as subject_name','subjects.credits_ECTS','subjects.difficult')->where([
                                    ['choises.subject_type', 'V'],
                                    ['choises.student_id', $student->id],
                                    ['choises.hidden', 0]
                                ])->get();
                        }
                        catch (Exception $e){
                            DB::rollback();
                            return response($e, 500);
                        }
                        try{
                            $vSubjects2 = DB::table('choises')
                                ->join('notes', 'notes.id', '=', 'choises.subject_id')
                                ->select('choises.id','notes.subject_name','notes.credits_ECTS','notes.difficult')->where([
                                    ['choises.subject_type', 'N'],
                                    ['choises.student_id', $student->id],
                                    ['choises.hidden', 0]
                                ])->get();
                        }
                        catch (Exception $e){
                            DB::rollback();
                            return response($e, 500);
                        }

                        foreach ($vSubjects1 as $subject){
                            try{
                                Students_studying_log::create([
                                    'student_id' => $student->id,
                                    'university' => "L",
                                    'date' => $date,
                                    'group_name' => $group->code,
                                    'difficult' => $subject->difficult,
                                    'credits_ECTS' => $subject->credits_ECTS,
                                    'semester' => $group->semester,
                                    'subject_title' => $subject->subject_name,
                                    'created_at' => $date,
                                    'updated_at' => $date,
                                ]);
                            }
                            catch (Exception $e){
                                DB::rollback();
                                return response($e, 500);
                            }

                            try{

                                $ch = Choise::find($subject->id);

                                $ch->hidden = 1;
                                $ch->updated_at = $date;
                                $ch->save();

                            }
                            catch (Exception $e){
                                DB::rollback();
                                return response($e, 500);
                            }
                        }

                        foreach ($vSubjects2 as $subject){
                            try{
                                Students_studying_log::create([
                                    'student_id' => $student->id,
                                    'university' => "L",
                                    'date' => $date,
                                    'group_name' => $group->code,
                                    'difficult' => $subject->difficult,
                                    'credits_ECTS' => $subject->credits_ECTS,
                                    'semester' => $group->semester,
                                    'subject_title' => $subject->subject_name,
                                    'created_at' => $date,
                                    'updated_at' => $date,
                                ]);
                            }
                            catch (Exception $e){
                                DB::rollback();
                                return response($e, 500);
                            }

                            try{

                                $ch = Choise::find($subject->id);

                                $ch->hidden = 1;
                                $ch->updated_at = $date;
                                $ch->save();

                            }
                            catch (Exception $e){
                                DB::rollback();
                                return response($e, 500);
                            }
                        }


                        foreach ($subjects as $subject){
                            try{
                                Students_studying_log::create([
                                    'student_id' => $student->id,
                                    'university' => "L",
                                    'date' => $date,
                                    'group_name' => $group->code,
                                    'difficult' => $subject->difficult,
                                    'credits_ECTS' => $subject->credits_ECTS,
                                    'semester' => $group->semester,
                                    'subject_title' => $subject->subject_name,
                                    'created_at' => $date,
                                    'updated_at' => $date,
                                ]);
                            }
                            catch (Exception $e){
                                DB::rollback();
                                return response($e, 500);
                            }

                        }
                    }

                    try{
                        $maxSemester = DB::table('notes')
                            ->join('plans', 'plans.id', '=', 'notes.plan_id')
                            ->where([
                                ['plans.group_id', $request->group_id],
                                ['notes.type', "N"],
                                ['plans.active', 1]
                            ])
                            ->max('notes.semester');
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }



                    try{
                        $group = Group::find ($request->group_id);
                        if($group->semester < $maxSemester){
                            $group->semester =  $group->semester + 1;
                        }else{
                            $group->semester =  100;
                        }

                        $group->updated_at = $date;

                        $group->save();
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }



                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Set group next semester',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        DB::rollback();
                        return response($e, 500);
                    }


                    DB::commit();



                    return response(json_encode("OK", JSON_UNESCAPED_UNICODE), 200);

                }else{
                    return response('forbidden', 403);
                }
            }else{
                return response('forbidden', 403);
            }
        }
    }
}
