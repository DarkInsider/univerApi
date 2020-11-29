<?php

namespace App\Http\Controllers;

use App\Choise;
use App\Log;
use App\Note;
use App\Plan;
use App\Student;
use App\Students_studying_log;
use App\Subject;
use App\SubjectRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\GetUser;
use App\Http\Helpers\ChoiseExport;
use Maatwebsite\Excel\Facades\Excel;
class ChoiseController extends Controller
{

    public function getChoiseInfoByStudentID(Request $request){
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $student = DB::table('students')
                    ->select('students.id','students.group_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if($student === null){
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
            $ret2 = DB::table('groups')
                ->join('plans', 'groups.id', '=', 'plans.group_id')
                ->select('plans.id as plan_id', 'groups.semester')->where([
                    ['groups.id', $student->group_id],
                    ['plans.active', 1],
                    ['plans.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }


        $globalRet = [];

        if ($ret2 != null) {
            try {
                $tmp = DB::table('notes')
                    ->select(DB::raw('SUM(credits_ECTS) as total_credits_ECTS'))->where([
                        ['notes.plan_id', $ret2->plan_id],
                        ['notes.semester', $ret2->semester],
                        ['notes.type', 'V'],
                        ['notes.hidden', 0]
                    ])
                    ->groupBy('semester')
                    ->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($tmp !== null) {
                $globalRet['hours'] = $tmp->total_credits_ECTS;
            } else {
                $globalRet['hours'] = 0;
            }


            $creditsSum = 0;

            try {
                $tmp = DB::table('choises')
                    ->select('choises.subject_id', 'choises.subject_type')->where([
                        ['choises.student_id', $request->student_id],
                        ['choises.hidden', 0]
                    ])
                    ->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($tmp) > 0) {
                foreach ($tmp as $item) {
                    if ($item->subject_type === 'V') {
                        try {
                            $sub1 = DB::table('subjects')
                                ->select('subjects.id', 'subjects.credits_ECTS')->where([
                                    ['subjects.id', $item->subject_id],
                                    ['subjects.hidden', 0],
                                    ['subjects.active', 1]
                                ])->first();
                        } catch (Exception $e) {
                            return response($e, 500);
                        }
                        if ($sub1 === null) {

                        } else {
                            $creditsSum += $sub1->credits_ECTS;
                        }
                    } else if ($item->subject_type === 'N') {
                        try {
                            $sub2 = DB::table('notes')
                                ->select('notes.id', 'notes.credits_ECTS')->where([
                                    ['notes.id', $item->subject_id],
                                    ['notes.hidden', 0]
                                ])->first();
                        } catch (Exception $e) {
                            return response($e, 500);
                        }
                        if ($sub2 === null) {
                        } else {
                            $creditsSum += $sub2->credits_ECTS;
                        }
                    }
                }
            }
            $globalRet['hours_selected'] = $creditsSum;
        }


        return response(json_encode($globalRet , JSON_UNESCAPED_UNICODE), 200);

    }



    public function getChoiseByStudentID(Request $request){
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $student = DB::table('students')
                    ->select('students.id','students.group_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if($student === null){
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
            $ret=Choise::select()
                ->where([['student_id',$request->student_id],['hidden', 0]])->get();
        } catch (Exception $e) {
            return response($e, 500);
        }


        foreach ($ret as $item){

            if($item->subject_type === 'V'){
                try {
                    $tmp=Subject::select()->where([['id',$item->subject_id],['hidden', 0]])->first();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                $item->subject_name=$tmp->title;
                $item->credits_ECTS=$tmp->credits_ECTS;
            }else if ($item->subject_type === 'N') {
                try {
                    $tmp=Note::select()->where([['id',$item->subject_id],['hidden', 0]])->first();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                $item->subject_name=$tmp->subject_name;
                $item->credits_ECTS=$tmp->credits_ECTS;
            }

        }
        return response(json_encode($ret , JSON_UNESCAPED_UNICODE), 200);

    }


    public function createChoise(Request $request){
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->student_id === null) {
            array_push($err, 'student_id is required');
        }else{
            try {
                $student = DB::table('students')
                    ->select('students.id','students.group_id')->where([
                        ['students.id', $request->student_id],
                        ['students.hidden', 0]
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if($student === null){
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
                ->select('students.id','students.group_id', 'groups.gradue_type')->where([
                    ['students.id', $request->student_id],
                    ['students.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }

        if ($student === null) {
            return response("Not student", 400);
        }

        $creditsSum=0;


        try {
            $isI = DB::table('students')
                ->select('students.id')->where([
                    ['students.id', $request->student_id],
                    ['students.user_id', $user->id],
                    ['students.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }

        if($isI === null){
            if($user->id != 1){
                try {
                    $ret = DB::table('possibility_has_roles')
                        ->select()->where([
                            ['possibility_has_roles.role_id', $user->role_id],
                            ['possibility_has_roles.possibility_id', 38],
                            ['possibility_has_roles.hidden', 0]
                        ])->get();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                if (count($ret) <= 0) {
                    return response('forbidden', 403);
                }
            }
        }





        if ($request->subject_ids === null) {
            array_push($err, 'subject_ids is required array[[id,type(V|N)],...]');
        }
        else{
            foreach ($request->subject_ids as $subject_id){
                try {
                    $tmp = DB::table('choises')
                        ->select('choises.id')->where([
                            ['choises.subject_id', $subject_id[0]],
                            ['choises.subject_type', $subject_id[1]],
                            ['choises.student_id',$student->id],
                            ['choises.hidden', 0]
                        ])->first();
                } catch (Exception $e) {
                    return response($e, 500);
                }
                if($tmp !== null){
                    array_push($err, 'subject with Id='.$subject_id[0].' and type='.$subject_id[1].' already exist in choises');
                    break;
                }


                if($subject_id[1] === 'V'){
                    try {
                        $sub1 = DB::table('subjects')
                            ->select('subjects.id','subjects.credits_ECTS')->where([
                                ['subjects.id', $subject_id[0]],
                                ['subjects.hidden', 0],
                                ['subjects.active', 1]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if($sub1 === null){
                        array_push($err, 'subject with Id='.$subject_id[0].' must exist in table SUBJECTS');
                        break;
                    }else{
                        $creditsSum+=$sub1->credits_ECTS;
                    }
                }else if($subject_id[1] === 'N'){
                    try {
                        $sub2 = DB::table('notes')
                            ->select('notes.id','notes.credits_ECTS')->where([
                                ['notes.id', $subject_id[0]],
                                ['notes.type', 'N'],
                                ['notes.hidden', 0]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if($sub2 === null){
                        array_push($err, 'subject with Id='.$subject_id[0].' must exist in table NOTES');
                        break;
                    }else{
                        $creditsSum+=$sub2->credits_ECTS;
                    }
                }else{
                    array_push($err, 'type must be N or V');
                    break;
                }
            }
        }
        if (count($err) > 0) {
            return response($err, 400);
        }

        try {
            $ret2 = DB::table('groups')
                ->join('plans', 'groups.id', '=', 'plans.group_id')
                ->select('plans.id as plan_id', 'groups.semester')->where([
                    ['groups.id', $student->group_id],
                    ['plans.active', 1],
                    ['plans.hidden', 0]
                ])->first();
        } catch (Exception $e) {
            return response($e, 500);
        }


        try {
            $tmp = DB::table('choises')
                ->select('subject_id','subject_type')->where([
                    ['choises.student_id', $student->id],
                    ['choises.hidden', 0]
                ])
                ->get();
        } catch (Exception $e) {
            return response($e, 500);
        }
        if(count($tmp) > 0) {
            foreach ($tmp as $item) {
                if ($item->subject_type === 'V') {
                    try {
                        $sub1 = DB::table('subjects')
                            ->select('subjects.id', 'subjects.credits_ECTS')->where([
                                ['subjects.id', $item->subject_id],
                                ['subjects.hidden', 0],
                                ['subjects.active', 1]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if ($sub1 === null) {

                    } else {
                        $creditsSum += $sub1->credits_ECTS;
                    }
                } else if ($item->subject_type === 'N') {
                    try {
                        $sub2 = DB::table('notes')
                            ->select('notes.id', 'notes.credits_ECTS')->where([
                                ['notes.id', $item->subject_id],
                                ['notes.hidden', 0]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if ($sub2 === null) {
                    } else {
                        $creditsSum += $sub2->credits_ECTS;
                    }
                }
            }
        }
        $hours =0;




        if ($ret2 != null) {
            try {
                $tmp = DB::table('notes')
                    ->select(DB::raw('SUM(credits_ECTS) as total_credits_ECTS'))->where([
                        ['notes.plan_id', $ret2->plan_id],
                        ['notes.semester', $ret2->semester],
                        ['notes.type', 'V'],
                        ['notes.hidden', 0]
                    ])
                    ->groupBy('semester')
                    ->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($tmp !== null) {
                $hours = $tmp->total_credits_ECTS;
            } else {
                $hours = 0;
            }
        }

        if( floatval($hours) !== floatval($creditsSum)){
            array_push($err, 'credits not equal. Need '.$hours.' given '.$creditsSum);
        }

        if (count($err) > 0) {
            return response($err, 400);
        }


        try {
            $plan = Plan::where([['group_id', $student->group_id], ['hidden', 0]])->get();
        }catch (Exception $e){
            return response($e, 500);
        }
        $planIds= [];
        foreach ($plan as $item){
            array_push($planIds, $item->id);
        }


        $isGood = true;
        foreach ($request->subject_ids as $subject_id){
            if($subject_id[1] === 'N'){


                try {
                    $nSubject = Note::select('id')->where([['id', $subject_id[0]],['hidden', 0]])
                        ->whereNotIn('plan_id', $planIds)->first();
                }catch (Exception $e){
                    return response($e, 500);
                }
                if($nSubject === null){
                    array_push($err, 'Subject with id = '. $subject_id[0].' not available for choise. Student in the same group ');
                    break;
                }

                try {
                    $tmpReq = SubjectRequirement::where([['subject_id', $nSubject->id]])->orderBy('subject_required_title')->get();
                }catch (Exception $e){
                    return response($e, 500);
                }
                $nSubject->reqirements = $tmpReq;

                try {
                    $learnedSubjects = Students_studying_log::where([['student_id', $student->id]])->orderBy('subject_title')->get();
                }catch (Exception $e){
                    return response($e, 500);
                }


                $reqLen = count($nSubject->reqirements);
                $flag = 0;


                for($i = 0; $i < $reqLen; $i++) {
                    $flag = 0;
                    foreach ($learnedSubjects as $learnedSubject) {
                        if ($nSubject->reqirements[$i]->subject_required_title === $learnedSubject->subject_title) {
                            $flag = 1;
                        }
                    }
                    if ($flag === 0) {
                        $isGood = false;
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


                    $isGood = true;
                    $a1 = count($reqArray);
                    $a2 = count($learnedSubjectsArray);
                    while ($isGood){
                        if($learnedSubjectsArray[$a2 -1 ][0] < $reqArray[$a1 - 1][0]){
                            $isGood = false;
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
                        $isGood = false;
                        break;
                    }else{
                        $isGood = true;
                    }




                    $i=$j;
                }
                if(!$isGood){
                   break;
                }
            }
        }

        if(!$isGood){
            array_push($err, 'not available N subject');
        }

        if (count($err) > 0) {
            return response($err, 400);
        }


        $date = date('Y-m-d H:i:s');
        DB::beginTransaction();
        foreach ($request->subject_ids as $subject_id) {
            try {
                $ret = Choise::create(
                    [
                        'date' => $date,
                        'subject_id' => intval($subject_id[0]),
                        'student_id' => intval($student->id),
                        'subject_type' => $subject_id[1],
                        'updated_at' => $date,
                        'created_at' => $date
                    ]
                );
            } catch (Exception $e) {
                DB::rollback();
                return response($e, 500);
            }
        }
        DB::commit();

        $date = date('Y-m-d H:i:s');
        try{
            Log::create([
                'user_id' => $user->id,
                'action' => 'Choice create',
                'updated_at' => $date,
                'created_at' => $date
            ]);
        }
        catch (Exception $e){
            return response($e, 500);
        }

        return response(json_encode("OK" , JSON_UNESCAPED_UNICODE), 200);


    }


    private function get_choises($request){

        $vSubjects = [];
        $nSubjects = [];

        $resp = [];


        switch (intval($request->type)){
            case 2: {
                try {
                    $vSubjects=Choise::select(
                        'choises.subject_id',
                        DB::raw('COUNT(choises.id) as count')
                    )
                        ->where([['choises.hidden', 0],['choises.subject_type', 'V']])
                        ->groupBy('choises.subject_id')
                        ->orderBy('count', 'desc')
                        ->get();
                }catch (Exception $e) {
                    $resp['code'] = 500;
                    $resp['message'] = 'Server Error';
                    $resp['data'] = $e;
                    return $resp;
                }


                foreach ($vSubjects as $vSubject){

                    try {
                        $ret = Subject::select(
                            'subjects.title',
                            'subjects.credits_ECTS'
                        )
                            ->where([['subjects.hidden', 0],['subjects.active', 1],['subjects.id', $vSubject->subject_id]])
                            ->first();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }

                    $vSubject->subject_name = $ret->title;
                    $vSubject->credits_ECTS = $ret->credits_ECTS;
                    $vSubject->type = 'V';

                    try {
                        $ret = Choise::select(
                            'choises.student_id',
                            'users.name',
                            'students.group_id',
                            'groups.code as group_code'
                        )
                            ->join('students', 'choises.student_id','students.id')
                            ->join('users', 'students.user_id', 'users.id')
                            ->join('groups', 'groups.id','students.group_id')
                            ->where([['choises.hidden', 0],['choises.subject_id', $vSubject->subject_id]])
                            ->get();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }
                    $vSubject->students = $ret;

                }

                $vSubjectIds = [];
                $vSubjects2 = [];
                foreach ($vSubjects as $vSubject){
                    array_push($vSubjectIds, $vSubject->subject_id);
                    array_push($vSubjects2, $vSubject);
                }


                try {
                    $ret = Subject::select(
                        'subjects.id as subject_id',
                        'subjects.title as subject_name',
                        'subjects.credits_ECTS'
                    )
                        ->where([['subjects.hidden', 0],['subjects.active', 1]])
                        ->whereNotIn('subjects.id', $vSubjectIds)
                        ->get();

                } catch (Exception $e) {
                    $resp['code'] = 500;
                    $resp['message'] = 'Server Error';
                    $resp['data'] = $e;
                    return $resp;
                }

                foreach ($ret as $vChoise){

                    $vChoise->type = 'V';
                    $vChoise->count = 0;
                    array_push($vSubjects2, $vChoise);

                }

                $vSubjects = $vSubjects2;

                try {
                    $nSubjects=Choise::select(
                        'choises.subject_id',
                        DB::raw('COUNT(choises.id) as count')
                    )
                        ->where([['choises.hidden', 0],['choises.subject_type', 'N']])
                        ->groupBy('choises.subject_id')
                        ->orderBy('count')
                        ->get();
                }catch (Exception $e) {
                    $resp['code'] = 500;
                    $resp['message'] = 'Server Error';
                    $resp['data'] = $e;
                    return $resp;
                }


                foreach ($nSubjects as $nSubject){

                    try {
                        $ret = Note::select(
                            'notes.subject_name',
                            'notes.credits_ECTS'
                        )
                            ->where([['notes.hidden', 0],['notes.id', $nSubject->subject_id]])
                            ->first();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }

                    $nSubject->type = 'N';
                    $nSubject->subject_name = $ret->subject_name;
                    $nSubject->credits_ECTS = $ret->credits_ECTS;

                    try {
                        $ret = Choise::select(
                            'choises.student_id',
                            'users.name',
                            'students.group_id',
                            'groups.code as group_code'
                        )
                            ->join('students', 'choises.student_id','students.id')
                            ->join('users', 'students.user_id', 'users.id')
                            ->join('groups', 'groups.id','students.group_id')
                            ->where([['choises.hidden', 0],['choises.subject_id', $nSubject->subject_id]])
                            ->get();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }
                    $nSubject->students = $ret;

                }

                $resp['data']['vChoises']=$vSubjects;
                $resp['data']['nChoises']=$nSubjects;
                $resp['code'] = 200;
                $resp['message'] = 'OK';
                return $resp;
            }
            case 1: {

                try {
                    $ret = Choise::select(
                        'choises.student_id'

                    )
                        ->where([['choises.hidden', 0]])
                        ->groupBy('choises.student_id')
                        ->get();

                }catch (Exception $e) {
                    $resp['code'] = 500;
                    $resp['message'] = 'Server Error';
                    $resp['data'] = $e;
                    return $resp;
                }

                foreach ($ret as $student){
                    try {
                        $tmp = Student::select(
                            'users.name',
                            'users.login',
                            'students.group_id',
                            'groups.code as group_code'
                        )
                            ->join('users', 'students.user_id', 'users.id')
                            ->join('groups', 'groups.id','students.group_id')
                            ->where([['students.hidden', 0],['students.id', $student->student_id],['groups.semester', '<>', 100]])
                            ->first();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }
                    $student->name = $tmp->name;
                    $student->login = $tmp->login;
                    $student->group_id = $tmp->group_id;
                    $student->group_code = $tmp->group_code;

                    try {
                        $ret2 = DB::table('groups')
                            ->join('plans', 'groups.id', '=', 'plans.group_id')
                            ->select('plans.id as plan_id', 'groups.semester')->where([
                                ['groups.id', $tmp->group_id],
                                ['plans.active', 1],
                                ['plans.hidden', 0]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }

                    if ($ret2 != null) {
                        try {
                            $ret3 = DB::table('notes')
                                ->select(DB::raw('SUM(credits_ECTS) as total_credits_ECTS'))->where([
                                    ['notes.plan_id', $ret2->plan_id],
                                    ['notes.semester', $ret2->semester],
                                    ['notes.type', 'V'],
                                    ['notes.hidden', 0]
                                ])
                                ->groupBy('semester')
                                ->first();
                        } catch (Exception $e) {
                            return response($e, 500);
                        }
                        if ($ret3 !== null) {
                            $student->hours_need = $ret3->total_credits_ECTS;
                        } else {
                            $student->hours_need = 0;
                        }
                    }

                    $vSub = [];
                    $nSub = [];
                    $student->hours_selected = 0;
                    try {
                        $vSub = Choise::select(
                            'choises.subject_id',
                            'subjects.title',
                            'subjects.credits_ECTS'
                        )
                            ->join('subjects', 'choises.subject_id', 'subjects.id')
                            ->where([['choises.hidden', 0],['choises.student_id', $student->student_id],['choises.subject_type', 'V']])
                            ->get();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }

                    foreach ($vSub as $item){
                        $student->hours_selected += $item->credits_ECTS;
                    }

                    try {
                        $nSub = Choise::select(
                            'choises.subject_id',
                            'notes.subject_name',
                            'notes.credits_ECTS'
                        )
                            ->join('notes', 'choises.subject_id', 'notes.id')
                            ->where([['choises.hidden', 0],['choises.student_id', $student->student_id],['choises.subject_type', 'N']])
                            ->get();

                    }catch (Exception $e) {
                        $resp['code'] = 500;
                        $resp['message'] = 'Server Error';
                        $resp['data'] = $e;
                        return $resp;
                    }
                    foreach ($nSub as $item){
                        $student->hours_selected += $item->credits_ECTS;
                    }
                    $tmp2['vChoises']=$vSub;
                    $tmp2['nChoises']=$nSub;
                    $student->subjects = $tmp2;
                }


                $vStudentIds = [];
                $retFin = [];
                foreach ($ret as $student){
                    array_push($vStudentIds, $student->student_id);
                    array_push($retFin, $student);
                }




                try {
                    $tmp = Student::select(
                        'students.id as student_id',
                        'users.name',
                        'users.login',
                        'students.group_id',
                        'groups.code as group_code'
                    )
                        ->join('users', 'students.user_id', 'users.id')
                        ->join('groups', 'groups.id','students.group_id')
                        ->where([['students.hidden', 0],['groups.semester', '<>', 100]])
                        ->whereNotIn('students.id',$vStudentIds )
                        ->get();

                }catch (Exception $e) {
                    $resp['code'] = 500;
                    $resp['message'] = 'Server Error';
                    $resp['data'] = $e;
                    return $resp;
                }

                foreach ($tmp as $student){


                    try {
                        $ret2 = DB::table('groups')
                            ->join('plans', 'groups.id', '=', 'plans.group_id')
                            ->select('plans.id as plan_id', 'groups.semester')->where([
                                ['groups.id', $student->group_id],
                                ['plans.active', 1],
                                ['plans.hidden', 0]
                            ])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }

                    if ($ret2 != null) {
                        try {
                            $ret3 = DB::table('notes')
                                ->select(DB::raw('SUM(credits_ECTS) as total_credits_ECTS'))->where([
                                    ['notes.plan_id', $ret2->plan_id],
                                    ['notes.semester', $ret2->semester],
                                    ['notes.type', 'V'],
                                    ['notes.hidden', 0]
                                ])
                                ->groupBy('semester')
                                ->first();
                        } catch (Exception $e) {
                            return response($e, 500);
                        }
                        if ($ret3 !== null) {
                            $student->hours_need = $ret3->total_credits_ECTS;
                        } else {
                            $student->hours_need = 0;
                        }
                    }
                    $student->hours_selected = 0;
                    $tmp2['vChoises']=[];
                    $tmp2['nChoises']=[];
                    $student->subjects = $tmp2;

                    array_push($retFin, $student);

                }

                $ret = $retFin;





                $resp['data']=$ret;
                $resp['code'] = 200;
                $resp['message'] = 'OK';
                return $resp;
            }
        }


    }

    public function getChoises(Request $request){
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->type === null) {
            array_push($err, 'type is required');
        }elseif ((intval($request->type) !== 1) &&(intval($request->type) !== 2)){
            array_push($err, 'type must be 1 (by students) or 2 (by subjects)');
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
            $ret = ChoiseController::get_choises($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
        } else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 37],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

            if (count($ret) > 0) {
                $ret = ChoiseController::get_choises($request);
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
            } else {
                return response('forbidden', 403);
            }
        }


    }

//    private function create_choise($request){
//        $date = date('Y-m-d H:i:s');
//        $response = [];
//        DB::beginTransaction();
//        $rets = [];
//        foreach ($request->subject_ids as $subject){
//            try {
//                $ret =Choise::create (
//                    [
//                        'date' => $date,
//                        'subject_id' => intval($subject) ,
//                        'student_id' => intval($request->student_id),
//                        'updated_at' => $date,
//                        'created_at'=> $date
//                    ]
//                );
//            } catch (\Exception $e) {
//                $response['code'] = 500;
//                $response['message'] = 'Server Error';
//                $response['data'] = $e;
//                DB::rollback();
//                return $response;
//            }
//            array_push($rets, $ret);
//        }
//
//        $response['code'] = 200;
//        $response['message'] = 'OK';
//        $response['data'] = $rets;
//        DB::commit();
//        return $response;
//    }
//
//
//    public function create(Request $request){
//        //Массив с выбраными предметами студента
//        //requests
//        $err = [];
//        $hours = 0;
//        if ($request->header('token') === null) {
//            array_push($err, 'token is required');
//        }
//        if ($request->student_id === null) {
//            array_push($err, 'student_id is required');
//        }else{
//            try {
//                $ret = DB::table('students')
//                    ->select('students.id')->where([
//                        ['students.id', $request->student_id],
//                        ['students.hidden', 0]
//                    ])->first();
//            } catch (Exception $e) {
//                return response($e, 500);
//            }
//            if ($ret === null) {
//                array_push($err, 'student must exist');
//            }
//        }
//        if (count($err) > 0) {
//            return response($err, 400);
//        }
//
//        if ($request->subject_ids === null) {
//            array_push($err, 'subject_ids is required (Array)');
//        } else {
//            $flag = false;
//            foreach ($request->subject_ids as $subject){
//                try {
//                    $ret = DB::table('subjects')
//                        ->select('subjects.id', 'subjects.hours')->where([
//                            ['subjects.id', $subject],
//                            ['subjects.hidden', 0]
//                        ])->first();
//                } catch (Exception $e) {
//                    return response($e, 500);
//                }
//                if ($ret === null) {
//                    $flag = true;
//                }else {
//                    $hours += $ret->hours;
//                }
//            }
//            try {
//                $ret2 = DB::table('choises')
//                    ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
//                    ->select('subjects.hours')->where([
//                        ['choises.student_id', $request->student_id],
//                        ['choises.hidden', 0]
//                    ])->get();
//            } catch (Exception $e) {
//                return response($e, 500);
//            }
//            foreach ($ret2 as $sub){
//                $hours+= $sub->hours;
//            }
//
//            if ($flag) {
//                array_push($err, 'subjects must exists');
//            }
//        }
//
//
//        if (count($err) > 0) {
//            return response($err, 400);
//        }
//
//
//        try {
//            $ret = DB::table('students')
//                ->select()->where([
//                    ['students.id', $request->student_id],
//                    ['students.hidden', 0]
//                ])->first();
//        } catch (Exception $e) {
//            return response($e, 500);
//        }
//
//        try {
//            $ret = DB::table('students')
//                ->join('plans', 'plans.group_id', '=', 'students.group_id')
//                ->join('notes', 'plans.id', '=', 'notes.plan_id')
//                ->select()->where([
//                    ['students.id', $request->student_id],
//                    ['plans.active', 1],
//                    ['notes.semester', $ret->semester],
//                    ['students.hidden', 0],
//                    ['plans.hidden', 0],
//                    ['notes.hidden', 0]
//                ])->first();
//        } catch (Exception $e) {
//            return response($e, 500);
//        }
//        if ($ret === null) {
//            array_push($err, 'operation not allowed');
//        }else{
//            if ($ret->hours !== $hours) {
//                array_push($err, 'hours not match');
//            }
//        }
//
//
//
////        $hours=0;
////        foreach ($ret2 as $sub){
////            $hours+= $sub->hours;
////        }
////
////        if ($ret->hours === $hours) {
////            array_push($err, 'student is already select subjects');
////        }
//
//
//
//        if (count($err) > 0) {
//            return response($err, 400);
//        }
//
//        $user = GetUser::get($request->header('token'));
//        if ($user === 'err') {
//            return response('server error', 500);
//        }
//        if ($user === null) {
//            return response('unauthorized', 401);
//        }
//
//
//        if($user->id === 1){  //Если суперюзер то сразу выполняем
//            $ret = ChoiseController::create_choise($request);
//            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
//        }else {
//            try{
//                $ret = DB::table('possibility_has_roles')
//                    ->select()->where([
//                        ['possibility_has_roles.role_id', $user->role_id],
//                        ['possibility_has_roles.possibility_id', 38],
//                        ['possibility_has_roles.hidden', 0]
//                    ])->get();
//            }
//            catch (Exception $e){
//                return response($e, 500);
//            }
//            try{
//                $ret2 = DB::table('students')
//                    ->select()->where([
//                        ['students.id', $request->student_id],
//                        ['students.hidden', 0]
//                    ])->first();
//            }
//            catch (Exception $e){
//                return response($e, 500);
//            }
//
//            if(count($ret)>0 || ($ret2->user_id === $user->id)) {
//                $ret = ChoiseController::create_choise($request);
//                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
//            } else {
//                return response('forbidden', 403);
//            }
//        }
//    }
//
    private function clear_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        DB::beginTransaction();
        try {
            DB::table('choises')
                ->where([['choises.subject_id', $request->subject_id],['choises.subject_type', 'V']])
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
        try {
            DB::table('subjects')
                ->where('subjects.id', $request->subject_id)
                ->update(
                    [
                        'active' => false,
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
        $response['message'] = 'Delete OK';
        $response['data'] = NULL;
        DB::commit();
        return $response;
    }

    public function subjectClear(Request $request){
        //Удалить несипользуемые предметы

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
            $ret = ChoiseController::clear_subject($request);
            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Subject clear',
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
                        ['possibility_has_roles.possibility_id', 40],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            if(count($ret)>0 ) {
                $ret = ChoiseController::clear_subject($request);
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
            } else {
                return response('forbidden', 403);
            }
        }
    }
//
//    private function get_choise_by_student_id($request){
//        try{
//            $ret = DB::table('choises')
//                ->join('students', 'students.id', '=', 'choises.student_id')
//                ->join('users', 'students.user_id', 'users.id')
//                ->join('groups', 'students.group_id', 'groups.id')
//                ->join('plans', 'plans.group_id', 'groups.id')
//                ->join('notes', 'plans.id', 'notes.plan_id')
//                ->select('students.id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login', 'notes.hours as hours_need')
//                ->where([
//                    ['students.id', $request->student_id],
//                    ['plans.active', 1],
//                    ['choises.hidden', 0]
//                ])
//                ->whereColumn([
//                    ['notes.semester', 'students.semester']
//                ])
//                ->distinct()
//                ->get();
//        }catch (Exception $e){
//            $response['code'] = 500;
//            $response['message'] = 'Server Error';
//            $response['data'] = $e;
//            return $response;
//        }
//
//        foreach ($ret as $stud){
//            $sum = 0;
//            try{
//                $ret2 = DB::table('choises')
//                    ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
//                    ->select('choises.subject_id', 'subjects.title', 'subjects.hours')
//                    ->where([
//                        ['choises.student_id', $stud->id],
//                        ['choises.hidden', 0]
//                    ])
//                    ->get();
//            }catch (Exception $e){
//                $response['code'] = 500;
//                $response['message'] = 'Server Error';
//                $response['data'] = $e;
//                return $response;
//            }
//            foreach ($ret2 as $hours){
//                $sum+=$hours->hours;
//            }
//            $stud->hours = $sum;
//            $stud->subjects = $ret2;
//        }
//        $response['code'] = 200;
//        $response['message'] = 'OK';
//        $response['data'] = $ret;
//        return $response;
//    }
//
//    public function getChoiseByStudentID(Request $request)
//    {
//        //requests
//        $err = [];
//        if ($request->header('token') === null) {
//            array_push($err, 'token is required');
//        }
//        if ($request->student_id === null) {
//            array_push($err, 'student_id is required');
//        }else{
//            try {
//                $ret = DB::table('students')
//                    ->select('students.id')->where([
//                        ['students.id', $request->student_id],
//                        ['students.hidden', 0]
//                    ])->first();
//            } catch (Exception $e) {
//                return response($e, 500);
//            }
//            if ($ret === null) {
//                array_push($err, 'student must exist');
//            }
//        }
//        if (count($err) > 0) {
//            return response($err, 400);
//        }
//
//        $user = GetUser::get($request->header('token'));
//        if ($user === 'err') {
//            return response('server error', 500);
//        }
//        if ($user === null) {
//            return response('unauthorized', 401);
//        }
//
//
//        if($user->id === 1){  //Если суперюзер то сразу выполняем
//            $ret = ChoiseController::get_choise_by_student_id($request);
//            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
//        }else {
//            try{
//                $ret = DB::table('possibility_has_roles')
//                    ->select()->where([
//                        ['possibility_has_roles.role_id', $user->role_id],
//                        ['possibility_has_roles.possibility_id', 37],
//                        ['possibility_has_roles.hidden', 0]
//                    ])->get();
//            }
//            catch (Exception $e){
//                return response($e, 500);
//            }
//            try{
//                $ret2 = DB::table('students')
//                    ->select()->where([
//                        ['students.id', $request->student_id],
//                        ['students.hidden', 0]
//                    ])->first();
//            }
//            catch (Exception $e){
//                return response($e, 500);
//            }
//
//            if(count($ret)>0 || ($ret2->user_id === $user->id)) {
//                $ret = ChoiseController::get_choise_by_student_id($request);
//                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
//            } else {
//                return response('forbidden', 403);
//            }
//        }
//
//
//    }
//
//
//    private function get_choises($request){
//        $response = [];
//        if(intval($request->type)  === 1){
//
//            try{
//                $ret = DB::table('choises')
//                    ->join('students', 'students.id', '=', 'choises.student_id')
//                    ->join('users', 'students.user_id', 'users.id')
//                    ->join('groups', 'students.group_id', 'groups.id')
//                    ->join('plans', 'plans.group_id', 'groups.id')
//                    ->join('notes', 'plans.id', 'notes.plan_id')
//                    ->select('students.id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login', 'notes.hours as hours_need')
//                    ->where([
//
//                        ['plans.active', 1],
//                        ['choises.hidden', 0]
//                    ])
//                    ->whereColumn([
//                        ['notes.semester', 'students.semester']
//                    ])
//                    ->distinct()
//                    ->get();
//            }catch (Exception $e){
//                $response['code'] = 500;
//                $response['message'] = 'Server Error';
//                $response['data'] = $e;
//                return $response;
//            }
//
//            foreach ($ret as $stud){
//                $sum = 0;
//                try{
//                  $ret2 = DB::table('choises')
//                      ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
//                      ->select('choises.subject_id', 'subjects.title', 'subjects.hours')
//                      ->where([
//                          ['choises.student_id', $stud->id],
//                          ['choises.hidden', 0]
//                      ])
//                      ->get();
//                }catch (Exception $e){
//                    $response['code'] = 500;
//                    $response['message'] = 'Server Error';
//                    $response['data'] = $e;
//                    return $response;
//                }
//                foreach ($ret2 as $hours){
//                    $sum+=$hours->hours;
//                }
//                $stud->hours = $sum;
//                $stud->subjects = $ret2;
//            }
//            $response['code'] = 200;
//            $response['message'] = 'OK';
//            $response['data'] = $ret;
//            return $response;
//        }
//        if(intval($request->type)  === 2){
//            try{
//                $ret = DB::table('choises')
//                    ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
//                    ->select('subjects.title', 'choises.subject_id')
//                    ->where([
//                        ['choises.hidden', 0]
//                    ])
//                    ->distinct()
//                    ->get();
//            }catch (Exception $e){
//                $response['code'] = 500;
//                $response['message'] = 'Server Error';
//                $response['data'] = $e;
//                return $response;
//            }
//
//            foreach ($ret as $subj){
//                try{
//                    $ret2 = DB::table('choises')
//                        ->join('students', 'students.id', '=', 'choises.student_id')
//                        ->join('users', 'students.user_id', 'users.id')
//                        ->join('groups', 'students.group_id', 'groups.id')
//                        ->select('choises.student_id', 'students.group_id', 'groups.code as group_code', 'students.user_id', 'users.name as student_name', 'users.login')
//                        ->where([
//                            ['choises.subject_id', $subj->subject_id],
//                            ['choises.hidden', 0]
//                        ])
//                        ->get();
//                }catch (Exception $e){
//                    $response['code'] = 500;
//                    $response['message'] = 'Server Error';
//                    $response['data'] = $e;
//                    return $response;
//                }
//                $subj->student_count = count($ret2);
//                $subj->students = $ret2;
//            }
//            try{
//                $ret3 = DB::table('subjects')
//                    ->select()
//                    ->where([
//                        ['subjects.active', 1],
//                        ['subjects.hidden', 0]
//                    ])
//                    ->get();
//            }catch (Exception $e){
//                $response['code'] = 500;
//                $response['message'] = 'Server Error';
//                $response['data'] = $e;
//                return $response;
//            }
//
//            foreach ($ret3 as $subject){
//                $tmp = 0;
//                $count = 0;
//                foreach ($ret as $item){
//                    if($item->subject_id === $subject->id){
//                        $tmp = 1;
//                    }
//                    $count++;
//                }
//                if($tmp === 0){
//                    $obj = (object) [] ;
//                    $obj->student_count =0;
//                    $obj->students =[];
//                    $obj->title =$subject->title;
//                    $obj->subject_id =$subject->id;
//
//                    $ret->add($obj);
//                }
//            }
//
//
//            $response['code'] = 200;
//            $response['message'] = 'OK';
//            $response['data'] = collect($ret)->sortBy('student_count')->reverse()->toArray(); ;
//            return $response;
//        }
//        $response['code'] = 400;
//        $response['message'] = 'type is wrong';
//        $response['data'] = NULL;
//        return $response;
//
//    }
//
//    public function get(Request $request){
//        //requests
//        $err = [];
//        if ($request->header('token') === null) {
//            array_push($err, 'token is required');
//        }
//
//        if ($request->type === null) {
//            array_push($err, 'type is required (1 - students vue, 2 - subjects vue');
//        }
//
//        if (count($err) > 0) {
//            return response($err, 400);
//        }
//
//        $user = GetUser::get($request->header('token'));
//        if ($user === 'err') {
//            return response('server error', 500);
//        }
//        if ($user === null) {
//            return response('unauthorized', 401);
//        }
//
//        if($user->id === 1){  //Если суперюзер то сразу выполняем
//            $ret = ChoiseController::get_choises($request);
//            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
//        }else {
//            try{
//                $ret = DB::table('possibility_has_roles')
//                    ->select()->where([
//                        ['possibility_has_roles.role_id', $user->role_id],
//                        ['possibility_has_roles.possibility_id', 37],
//                        ['possibility_has_roles.hidden', 0]
//                    ])->get();
//            }
//            catch (Exception $e){
//                return response($e, 500);
//            }
//
//            if(count($ret)>0 ) {
//                $ret = ChoiseController::get_choises($request);
//                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), $ret['code']);
//            } else {
//                return response('forbidden', 403);
//            }
//        }
//    }
//
    public function export(Request $request){
        //requests
        $err = [];
        if ($request->token === null) {
            array_push($err, 'token is required');
        }

        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->token);
        if ($user === 'err') {
            return response('server error', 500);
        }
        if ($user === null) {
            return response('unauthorized', 401);
        }

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            return Excel::download(new ChoiseExport(), 'choise.xlsx');
        }else {
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 37],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            if(count($ret)>0 ) {
                return Excel::download(new ChoiseExport(), 'choise.xlsx');
            } else {
                return response('forbidden', 403);
            }
        }



    }
}
