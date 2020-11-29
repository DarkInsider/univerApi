<?php

namespace App\Http\Controllers;

use App\Lecturer_has_subject;
use App\Log;
use App\Note;
use App\Plan;
use App\Students_studying_log;
use App\Subject;
use App\SubjectRequirement;
use Illuminate\Http\Request;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;
use Illuminate\Support\Facades\DB;
use App\Group;

class SubjectController extends Controller
{

    public function getAvailableSubjects(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $user_id = DB::table('students')
                    ->select('students.user_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if($user_id === null){
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



        try {
            $student = DB::table('students')
                ->join('groups', 'groups.id', 'students.group_id')
                ->select('students.id', 'students.group_id', 'groups.gradue_type')->where([
                    ['students.user_id', $user_id->user_id],
                    ['students.hidden', 0]
                ])->get();
        } catch (Exception $e) {
            return response($e, 500);
        }

        $tmpArr = [];
        $gradue_types =[];
        foreach ($student as $item){
            if($item->gradue_type === 'B'){
                array_push($gradue_types, 'B');
            }
            if($item->gradue_type === 'M'){
                array_push($gradue_types, 'M');
            }
            array_push($tmpArr,$item);
        }



//        try {
//            $student2 = DB::table('students')
//                ->select('students.id', 'students.group_id')->where([
//                    ['students.user_id', $user->id],
//                    ['students.hidden', 1]
//                ])->get();
//        } catch (Exception $e) {
//            return response($e, 500);
//        }

        if(count($student)){

            try {
                $plan = Plan::where([ ['hidden', 0]])->whereIn('group_id', array_map(function ($e){return $e->group_id;},$tmpArr))->get();
            }catch (Exception $e){
                return response($e, 500);
            }

            try {
                $vSubjects = Subject::select('subjects.id', 'subjects.title', 'subjects.credits_ECTS', 'subjects.semester','subjects.difficult', 'subjects.lecturer_id','users.name as lecturer_name', 'subjects.department_id', 'departments.title as department_name' )
                    ->join('lecturers', 'subjects.lecturer_id', 'lecturers.id')
                    ->join('users', 'lecturers.user_id', 'users.id')
                    ->join('departments', 'users.department_id', 'departments.id')
                    ->where([['subjects.active', 1], ['subjects.hidden', 0]])
                    ->whereIn('subjects.gradue_type', $gradue_types)
                    ->get();
            }catch (Exception $e){
                return response($e, 500);
            }


            $planIds= [];
            foreach ($plan as $item){
                array_push($planIds, $item->id);
            }

            try {
                $nSubjects = Note::select('id', 'type','subject_name','semester', 'plan_id','zalic_or_examen', 'z_or_e_number', 'cours_projects', 'cours_work', 'leccii','laborat','practik','samostiyna_robta','weeks_in_semester','difficult','par_per_week','credits_ECTS')->where([['hidden', 0],['type', 'N']])->whereNotIn('plan_id', $planIds)->get();
            }catch (Exception $e){
                return response($e, 500);
            }

            foreach ($nSubjects as $nSubject){
                try {
                    $tmpReq = SubjectRequirement::where([['subject_id', $nSubject->id]])->orderBy('subject_required_title')->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
                $nSubject->reqirements = $tmpReq;
                try {
                    $tmpReq = Lecturer_has_subject::select('lecturer_has_subjects.id','lecturers.id as lecturer_id','users.name as lecturer_name', 'users.department_id', 'departments.title as department_name' )
                        ->join('lecturers', 'lecturer_has_subjects.lecturer_id', 'lecturers.id')
                        ->join('users', 'lecturers.user_id', 'users.id')
                        ->join('departments', 'users.department_id', 'departments.id')
                        ->where([['lecturer_has_subjects.subject_id', $nSubject->id]])->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
                $nSubject->lecturers = $tmpReq;
            }

            $studentIds= [];
            foreach ($student as $item){
                array_push($studentIds, $item->id);
            }

            try {
                $learnedSubjects = Students_studying_log::where([['hidden', 0]])->whereIn('student_id',$studentIds)->orderBy('subject_title')->get();
            }catch (Exception $e){
                return response($e, 500);
            }

            $reqArray2 = []; //del

          //  $reqArray3 = []; //del
            foreach ($nSubjects as $nSubject){
                $reqLen = count($nSubject->reqirements);
                $flag = 0;
                $tmpFlag = true;

                for($i = 0; $i < $reqLen; $i++) {
                    $flag = 0;
                    foreach ($learnedSubjects as $learnedSubject) {
                        if ($nSubject->reqirements[$i]->subject_required_title === $learnedSubject->subject_title) {
                            $flag = 1;
                        }
                    }
                    if ($flag === 0) {
                        $tmpFlag = false;
                        break;
                    }


                    $reqArray = [];
                    array_push($reqArray, [$nSubject->reqirements[$i]->difficult, $nSubject->reqirements[$i]->credits_ECTS, $nSubject->reqirements[$i]->subject_required_title]);

                    $j = $i;
                    while(($j + 1 < $reqLen)&&($nSubject->reqirements[$j]->subject_required_title === $nSubject->reqirements[$j + 1]->subject_required_title)){
                        $stop = -1;
                        for($counter = 0; $counter < count($reqArray); $counter++){
                            if($nSubject->reqirements[$j+1]->difficult === $reqArray[$counter][0]){
                                $stop = $counter;
                                break;
                            }
                        }
                        if ($stop >= 0) {
                            $reqArray[$stop][1] += $nSubject->reqirements[$j + 1]->credits_ECTS;
                        } else {
                            array_push($reqArray, [$nSubject->reqirements[$j + 1]->difficult, $nSubject->reqirements[$j + 1]->credits_ECTS, $nSubject->reqirements[$j]->subject_required_title]);
                        }

                        $j++;
                    }

                    usort($reqArray, function ($a, $b)
                    {
                        if ($a[0] == $b[0]) {
                            return 0;
                        }
                        return ($a[0] < $b[0]) ? -1 : 1;

                    }
                    );

                    //ТУТ ОК

                    $learnedSubjectsLen = count($learnedSubjects);
                    $learnedSubjectsArray = [];

                    $counter = 0;
                    while (($counter < $learnedSubjectsLen)&&($nSubject->reqirements[$i]->subject_required_title !== $learnedSubjects[$counter]->subject_title)){
                        $counter++;
                    }

                    if($counter >= $learnedSubjectsLen){
                        break;
                    }

                    array_push($learnedSubjectsArray, [$learnedSubjects[$counter]->difficult, $learnedSubjects[$counter]->credits_ECTS,  $learnedSubjects[$counter]->subject_title]);

                    while (($counter + 1 < $learnedSubjectsLen)&&($learnedSubjects[$counter]->subject_title === $learnedSubjects[$counter + 1]->subject_title)){
                        $stop = -1;
                        for($counter2 = 0; $counter2 < count($learnedSubjectsArray); $counter2++){
                            if($learnedSubjects[$counter + 1]->difficult === $learnedSubjectsArray[$counter2][0]){
                                $stop = $counter2;
                                break;
                            }
                        }
                        if ($stop >= 0) {
                            $learnedSubjectsArray[$stop][1] +=$learnedSubjects[$counter + 1]->credits_ECTS;
                        } else {
                            array_push($learnedSubjectsArray, [$learnedSubjects[$counter + 1]->difficult, $learnedSubjects[$counter + 1]->credits_ECTS]);
                        }

                        $counter++;

                    }

                    usort($learnedSubjectsArray, function ($a, $b)
                    {
                        if ($a[0] == $b[0]) {
                            return 0;
                        }
                        return ($a[0] < $b[0]) ? -1 : 1;

                    }
                    );


                    $tmpFlag = true;
                    $a1 = count($reqArray);
                    $a2 = count($learnedSubjectsArray);
                    while ($tmpFlag){
                        if($learnedSubjectsArray[$a2 -1 ][0] < $reqArray[$a1 - 1][0]){
                            $tmpFlag = false;
                            break;
                        }else{
                            $learnedSubjectsArray[$a2 -1][1] = $learnedSubjectsArray[$a2 -1][1] - $reqArray[$a1 -1][1];
                            if( $learnedSubjectsArray[$a2 -1][1] < 0){
                                if($a2 -2 >= 0){
                                    $learnedSubjectsArray[$a2 -2 ][1] +=  $learnedSubjectsArray[$a2 -1][1];
                                }

                                $a2--;
                            }else{
                                $a1--;
                            }if($a1 <= 1 || $a2 <= 1){
                                break;
                            }
                        }

                    }
                   // array_push($reqArray3, [$reqArray,$learnedSubjectsArray, $a1, $a2]); //del
                    if ($a1 >= $a2){
                        $tmpFlag = false;
                        break;
                    }else{
                        $tmpFlag = true;
                    }




                    $i=$j;
                }
                if($tmpFlag){
                    array_push($reqArray2, $nSubject); //del
                }
            }

        }

        $resp = [];
        foreach ($vSubjects as $vSubject){
            $vSubject->s_type = 'V';
            array_push($resp, $vSubject);
        }
        foreach ($reqArray2 as $item){
            $item->s_type = 'N';
            array_push($resp, $item);
        }

        return response(json_encode($resp , JSON_UNESCAPED_UNICODE), 200);
    }

    static private  function create_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];

        $tmpArr =[
            'lecturer_id' => $request->lecturer_id,
            'department_id' => $request->department_id,
            'type' => $request->type,
            'title' => $request->title,
            'credits_ECTS' => $request->hours,
            'subject_description' => $request->html,
            'updated_at' => $date,
            'created_at'=> $date
        ];

        if ($request->gradue_type !== null){
            $tmpArr['gradue_type']=$request->gradue_type;
        }

        try {
            $ret =Subject::create ($tmpArr);
        } catch (\Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $ret;
        return $response;
    }

    public function create(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }
        if ($request->type === null) {
            array_push($err, 'type is required');
        }
        if ($request->hours === null) {
            array_push($err, 'hours is required');
        }
        if ($request->department_id === null) {
            array_push($err, 'department_id is required');
        } else {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if ($request->lecturer_id === null) {
            array_push($err, 'lecturer_id is required');
        } else {
            try {
                $ret = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'lecturer must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }
        try {
            $ret = DB::table('department_has_lecturers')
                ->select('department_has_lecturers.id')->where([
                    ['department_has_lecturers.lecturer_id', $request->lecturer_id],
                    ['department_has_lecturers.department_id', $request->department_id],
                    ['department_has_lecturers.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if ($ret === null) {
            array_push($err, 'lecturer must exist on selected department');
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




        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = SubjectController::create_subject($request);
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Subject create',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 34],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
                $flag = false;
                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($facultyReq->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($facultyReq->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($request->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($request->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = SubjectController::create_subject($request);
                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Subject create',
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


    public function get(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->department_id !== null) {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if ($request->lecturer_id !== null) {
            try {
                $ret = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'lecturer must exist');
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
            if ($request->lecturer_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.lecturer_id', $request->lecturer_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
            if ($request->department_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.department_id', $request->department_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }

            try {
                $ret = DB::table('subjects')
                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                    ->join('users', 'users.id', 'lecturers.user_id')
                    ->join('departments', 'departments.id', 'subjects.department_id')
                    ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                        ['subjects.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }






        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if ($request->lecturer_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.lecturer_id', $request->lecturer_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
            if ($request->department_id !== null) {
                try {
                    $ret = DB::table('subjects')
                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                        ->join('users', 'users.id', 'lecturers.user_id')
                        ->join('departments', 'departments.id', 'subjects.department_id')
                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                            ['subjects.department_id', $request->department_id],
                            ['subjects.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }

            try {
                $ret = DB::table('subjects')
                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                    ->join('users', 'users.id', 'lecturers.user_id')
                    ->join('departments', 'departments.id', 'subjects.department_id')
                    ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                        ['subjects.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 33],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {

                $subjects=[];
                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.faculty_id', $faculty->faculty_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.faculty_id', $faculty->faculty_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', $faculty->faculty_id],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);

                            continue;
                        } else {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.id', $user->department_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.id', $user->department_id],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.id', $user->department_id],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);
                            continue;
                        } else {
                            if ($request->lecturer_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title',  'subjects.gradue_type','subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.lecturer_id', $request->lecturer_id],
                                            ['departments.id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }
                            if ($request->department_id !== null) {
                                try {
                                    $ret = DB::table('subjects')
                                        ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                        ->join('users', 'users.id', 'lecturers.user_id')
                                        ->join('departments', 'departments.id', 'subjects.department_id')
                                        ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                            ['subjects.department_id', $request->department_id],
                                            ['departments.id', intval($item->scope)],
                                            ['subjects.hidden', 0]
                                        ])->get();
                                } catch (Exception $e) {
                                    return response($e, 500);
                                }
                                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                            }

                            try {
                                $ret = DB::table('subjects')
                                    ->join('lecturers', 'lecturers.id', 'subjects.lecturer_id')
                                    ->join('users', 'users.id', 'lecturers.user_id')
                                    ->join('departments', 'departments.id', 'subjects.department_id')
                                    ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.type', 'subjects.credits_ECTS', 'subjects.active','subjects.lecturer_id', 'users.name as lecturer_name','subjects.department_id', 'departments.title as department_title')->where([
                                        ['departments.id', intval($item->scope)],
                                        ['subjects.hidden', 0]
                                    ])->get();
                            } catch (Exception $e) {
                                return response($e, 500);
                            }
                            array_push($subjects, $ret);
                            continue;
                        }
                    }
                }
            }
        }

        return response(  json_encode(Normalize::normalize($subjects), JSON_UNESCAPED_UNICODE), 200);

    }

    public function getById(Request $request, $id){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
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
        try {
            $ret = DB::table('subjects')
                ->join('lecturers', 'lecturers.id', '=', 'subjects.lecturer_id')
                ->join('users', 'users.id', '=', 'lecturers.user_id')
                ->join('departments', 'departments.id', '=', 'subjects.department_id')
                ->select('subjects.id', 'subjects.title', 'subjects.gradue_type', 'subjects.subject_description', 'subjects.type','subjects.difficult',  'subjects.credits_ECTS', 'subjects.active', 'subjects.lecturer_id','users.name as lecturer_name', 'subjects.department_id', 'departments.title as department_title')->where([
                    ['subjects.id', $id],
                    ['subjects.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
    }


    private function update_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];



        $inp =   [
            'lecturer_id' => $request->lecturer_id,
            'department_id' => $request->department_id,
            'title' => $request->title,
            'credits_ECTS' => $request->hours,
            'subject_description' => $request->html,
            'updated_at' => $date,
        ];

        if($request->difficult !== null){
            $inp['difficult']=$request->difficult;
        }

        if($request->active !== null){
            $inp['active']=$request->active;
        }

        if ($request->gradue_type !== null){
            $inp['gradue_type']=$request->gradue_type;
        }


        try {
            DB::table('subjects')
                ->where('subjects.id', $request->subject_id)
                ->update(
                  $inp
                );
        } catch (Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        try {
            $ret = DB::table('subjects')
                ->select('subjects.id', 'subjects.title', 'subjects.subject_description', 'subjects.type','subjects.difficult', 'subjects.credits_ECTS', 'subjects.lecturer_id','subjects.department_id')->where([
                    ['subjects.id', $request->subject_id],
                    ['subjects.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            return $response;
        }
        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $ret;
        return $response;
    }




    public function update(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->title === null) {
            array_push($err, 'title is required');
        }

        if ($request->hours === null) {
            array_push($err, 'hours is required');
        }
        if ($request->subject_id === null) {
            array_push($err, 'subject_id is required');
        } else {
            try {
                $ret = DB::table('subjects')
                    ->select('subjects.id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'subject must exist');
            }
        }
        if ($request->department_id === null) {
            array_push($err, 'department_id is required');
        } else {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if ($request->lecturer_id === null) {
            array_push($err, 'lecturer_id is required');
        } else {
            try {
                $ret = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'lecturer must exist');
            }
        }

        if (count($err) > 0) {
            return response($err, 400);
        }
        try {
            $ret = DB::table('department_has_lecturers')
                ->select('department_has_lecturers.id')->where([
                    ['department_has_lecturers.lecturer_id', $request->lecturer_id],
                    ['department_has_lecturers.department_id', $request->department_id],
                    ['department_has_lecturers.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if ($ret === null) {
            array_push($err, 'lecturer must exist on selected department');
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = SubjectController::update_subject($request);
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Subject update',
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
                        ['possibility_has_roles.possibility_id', 35],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flag1 = false;
                $flag2 = false;
                $facultyReq = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $request->department_id],
                ])->first();

                $facultyReqOld =  DB::table('subjects')
                    ->join('departments', 'departments.id', '=', 'subjects.department_id')
                    ->select('subjects.id','subjects.department_id', 'departments.faculty_id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($facultyReq->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($faculty->faculty_id) === intval($facultyReqOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($facultyReq->faculty_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($facultyReqOld->faculty_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($request->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($user->department_id) === intval($facultyReqOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($request->department_id)) {
                                $flag1 = true;
                            }
                            if (intval($item->scope) === intval($facultyReqOld->department_id)) {
                                $flag2 = true;
                            }
                            continue;
                        }
                    }
                }
                if ($flag1 && $flag2) {
                    $ret = SubjectController::update_subject($request);
                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Subject update',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                } else {
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


    private function delete_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        DB::beginTransaction();
        try {
            DB::table('subjects')
                ->where('subjects.id', $request->subject_id)
                ->update(
                    [
                        'hidden' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            DB::rollback();
            return $response;
        }
        try {
            DB::table('choises')
                ->where('choises.subject_id', $request->subject_id)
                ->update(
                    [
                        'hidden' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (\Exception $e) {
            $response['code'] = 500;
            $response['message'] = 'Server Error';
            $response['data'] = $e;
            DB::rollback();
            return $response;
        }

        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = 'Delete OK';
        DB::commit();
        return $response;
    }


    public function delete(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->subject_id === null) {
            array_push($err, 'subject_id is required');
        } else {
            try {
                $ret = DB::table('subjects')
                    ->select('subjects.id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'subject must exist');
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = SubjectController::delete_subject($request);
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Subject delete',
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
                        ['possibility_has_roles.possibility_id', 35],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flag = false;
                $facultyReqOld =  DB::table('subjects')
                    ->join('departments', 'departments.id', '=', 'subjects.department_id')
                    ->select('subjects.id','subjects.department_id', 'departments.faculty_id')->where([
                        ['subjects.id', $request->subject_id],
                        ['subjects.hidden', 0]
                    ])->first();

                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($faculty->faculty_id) === intval($facultyReqOld->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($facultyReqOld->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($facultyReqOld->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($facultyReqOld->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($flag) {
                    $ret = SubjectController::delete_subject($request);
                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Subject delete',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }
                    return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
                } else {
                    return response('forbidden', 403);
                }
            } else {
                return response('forbidden', 403);
            }
        }
    }


}
