<?php

namespace App\Http\Controllers;

use App\Lecturer_has_subject;
use App\Log;
use App\Note;
use App\Plan;
use App\SubjectRequirement;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

class NoteController extends Controller
{

    public function getNormativeSubjects(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if(($request->limit !== null)&&($request->limit <= 0)){
            array_push($err, 'limit is wrong');
        }
        if($request->group_id !== null){
            try{
                $group = DB::table('groups')
                    ->select('groups.id')->where([
                        ['groups.id', $request->group_id],
                        ['groups.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($group === null){
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

        //група
        //семестр
        //пагинация
        //лимит страниц
        //по названию предмета

        $filters = [['type', 'N']];

        if($request->group_id !== null){
            $plan = Plan::where([['group_id', $request->group_id], ['active', 1]])->first();
            if($plan !== null){
                $tmp = ['plan_id', $plan->id];
                array_push($filters, $tmp);
            }
            else {
                $tmp = ['id', -1];
                array_push($filters, $tmp);
            }
        }

        if($request->semester !== null){
            $tmp = ['semester', $request->semester];
            array_push($filters, $tmp);
        }

        if($request->search_str !== null){
            $tmp = ['subject_name','like', '%'.$request->search_str.'%'];
            array_push($filters, $tmp);
        }

        try{
            $subjects = Note::where($filters)
//            ->orderBy('name', 'desc')
//            ->take(10)
            ->paginate($request->limit !== null ? $request->limit : 12);
        }
        catch (Exception $e){
            return response($e, 500);
        }


        return response(json_encode($subjects, JSON_UNESCAPED_UNICODE), 200);



    }


    public function getNormativeSubjectById(Request $request, $id){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($id === null){
            array_push($err, 'id is required');
        }else {
            try{
                $ret = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'note must exist');
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

        $ret = [];

        try{
           $ret['subject'] = Note::find($id);
        }
        catch (Exception $e){
            return response($e, 500);
        }
        try{
            $ret['requirements'] = SubjectRequirement::where('subject_id', $id)->get();
        }
        catch (Exception $e){
            return response($e, 500);
        }

        try{
            $ret['lecturers'] = DB::table('lecturer_has_subjects')
                ->join('lecturers', 'lecturer_has_subjects.lecturer_id', 'lecturers.id')
                ->join('users', 'users.id','lecturers.user_id')
                ->join('departments', 'departments.id', 'users.department_id')
                ->join('faculties', 'departments.faculty_id', 'faculties.id')
                ->select('lecturer_has_subjects.id','lecturers.id as lecturer_id', 'users.name as lecturer_name', 'departments.title as department_title', 'faculties.title as faculty_title')->where([
                    ['lecturer_has_subjects.subject_id', $id],
                    ['lecturer_has_subjects.hidden', 0]
                ])->get();



        }
        catch (Exception $e){
            return response($e, 500);
        }




        return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

    }




    public function unPinSubjectRequirement(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->requirement_id === null){
            array_push($err, 'requirement_id is required');
        }else {
            try{
                $ret = DB::table('subject_requirements')
                    ->select('subject_requirements.id', 'subject_requirements.subject_id')->where([
                        ['subject_requirements.id', $request->requirement_id],
                        ['subject_requirements.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'requirement must exist');
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
            try{
                SubjectRequirement::destroy($request->requirement_id);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Subject requirement unpin',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
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
                 try{
                     $reqParam = DB::table('subject_requirements')
                         ->join('notes', 'notes.id','subject_requirements.subject_id')
                         ->join('plans', 'plans.id','notes.plan_id')
                         ->join('groups', 'groups.id','plans.group_id')
                         ->join('departments', 'departments.id','groups.department_id')
                         ->select('departments.faculty_id', 'groups.department_id')->where([
                             ['subject_requirements.id', $request->requirement_id],
                             ['subject_requirements.hidden', 0]
                         ])->first();
                 }
                 catch (Exception $e){
                     return response($e, 500);
                 }

                 $flag = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }

                if($flag === true){
                    try{
                        SubjectRequirement::destroy($request->requirement_id);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Subject requirement unpin',
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
            }else{
                return response('forbidden', 403);
            }
        }
    }

    public function pinSubjectRequirement(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->subject_id === null){
            array_push($err, 'subject_id is required');

        }else {
            try{
                $ret = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $request->subject_id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'subject must exist');
            }
        }
        if($request->required_subject_ids === null){
            array_push($err, 'required_subject_ids is required');

        }else {
            foreach ($request->required_subject_ids as $required_subject_id)
            {
                try{
                    $ret = DB::table('notes')
                        ->select('notes.id')->where([
                            ['notes.id', $required_subject_id],
                            ['notes.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }
                if($ret === null){
                    array_push($err, 'required_subject_ids must exist');
                    break;
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


        if($user->id === 1){
            $date = date('Y-m-d H:i:s');
            foreach ($request->required_subject_ids as $required_subject_id){
                try{
                    $note = Note::find($required_subject_id);
                    $ret = SubjectRequirement::create(
                        [
                            'subject_required_title' => $note->subject_name,
                            'difficult' => $note->difficult,
                            'credits_ECTS' => $note->credits_ECTS,
                            'semester' => $note->semester,
                            'subject_id' => $request->subject_id,
                            'created_at' => $date,
                            'updated_at' => $date,
                        ]
                    );
                }
                catch (Exception $e){
                    return response($e, 500);
                }
            }


            $ret['subject_id']=$request->subject_id;

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Subject requirement pin',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
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
                try{
                    $reqParam = DB::table('notes')
                        ->join('plans', 'plans.id','notes.plan_id')
                        ->join('groups', 'groups.id','plans.group_id')
                        ->join('departments', 'departments.id','groups.department_id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['notes.id', $request->subject_id],
                            ['notes.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }

                $flag = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();




                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    }
                }

                if($flag === true){
                    $date = date('Y-m-d H:i:s');
                    foreach ($request->required_subject_ids as $required_subject_id){
                        try{
                            $note = Note::find($required_subject_id);
                            $ret = SubjectRequirement::create(
                                [
                                    'subject_required_title' => $note->subject_name,
                                    'difficult' => $note->difficult,
                                    'credits_ECTS' => $note->credits_ECTS,
                                    'semester' => $note->semester,
                                    'subject_id' => $request->subject_id,
                                    'created_at' => $date,
                                    'updated_at' => $date,
                                ]
                            );
                        }
                        catch (Exception $e){
                            return response($e, 500);
                        }
                    }


                    $ret['subject_id']=$request->subject_id;

                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Subject requirement pin',
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
            }else{
                return response('forbidden', 403);
            }
        }
    }


    public function pinLecturerToSubject(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->lecturer_id === null){
            array_push($err, 'lecturer_id is required');
        }else {
            try{
                $retL = DB::table('lecturers')
                    ->select('lecturers.id')->where([
                        ['lecturers.id', $request->lecturer_id],
                        ['lecturers.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($retL === null){
                array_push($err, 'lecturer must exist');
            }
        }
        if($request->subject_id === null){
            array_push($err, 'subject_id is required');

        }else {
            try{
                $retS = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $request->subject_id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($retS === null){
                array_push($err, 'subject must exist');
            }
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $ret = DB::table('lecturer_has_subjects')
                ->select('lecturer_has_subjects.id')->where([
                    ['lecturer_has_subjects.lecturer_id', $request->lecturer_id],
                    ['lecturer_has_subjects.subject_id', $request->subject_id],
                    ['lecturer_has_subjects.hidden', 0]
                ])->first();
        }
        catch (Exception $e){
            return response($e, 500);
        }
        if($ret !== null){
            array_push($err, 'lecturer already pinned to subject');
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
            $date = date('Y-m-d H:i:s');
            try{
                $ret = Lecturer_has_subject::create(
                    [
                        'lecturer_id' => $request->lecturer_id,
                        'subject_id' => $request->subject_id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
            }
            catch (Exception $e){
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Lecturer to subject pin',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
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
                try{
                    $reqParam = DB::table('notes')
                        ->join('plans', 'plans.id','notes.plan_id')
                        ->join('groups', 'groups.id','plans.group_id')
                        ->join('departments', 'departments.id','groups.department_id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['notes.id', $request->subject_id],
                            ['notes.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }

                $flag = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    }
                }

                if($flag === true){
                    $date = date('Y-m-d H:i:s');
                    try{
                        $ret = Lecturer_has_subject::create(
                            [
                                'lecturer_id' => $request->lecturer_id,
                                'subject_id' => $request->subject_id,
                                'created_at' => $date,
                                'updated_at' => $date,
                            ]
                        );
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Lecturer to subject pin',
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
            }else{
                return response('forbidden', 403);
            }
        }







    }

    public function unPinLecturerFromSubject(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->lecturer_has_subject_id === null){
            array_push($err, 'lecturer_has_subject_id is required');
        }else {
            try{
                $ret = DB::table('lecturer_has_subjects')
                    ->select('lecturer_has_subjects.id', 'subject_id')->where([
                        ['lecturer_has_subjects.id', $request->lecturer_has_subject_id],
                        ['lecturer_has_subjects.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'lecturer_has_subject must exist');
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
            try{
                Lecturer_has_subject::destroy($request->lecturer_has_subject_id);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Lecturer to subject unpin',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
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
                try{
                    $reqParam = DB::table('lecturer_has_subjects')
                        ->join('notes', 'notes.id','lecturer_has_subjects.subject_id')
                        ->join('plans', 'plans.id','notes.plan_id')
                        ->join('groups', 'groups.id','plans.group_id')
                        ->join('departments', 'departments.id','groups.department_id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['lecturer_has_subjects.id', $request->lecturer_has_subject_id],
                            ['lecturer_has_subjects.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }

                $flag = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    }
                }

                if($flag === true){
                    try{
                        Lecturer_has_subject::destroy($request->lecturer_has_subject_id);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Lecturer to subject unpin',
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
            }else{
                return response('forbidden', 403);
            }
        }
    }


    public function updateSubjectDescription(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->subject_description === null){
            array_push($err, 'subject_description is required');
        }
        if($request->note_id === null){
            array_push($err, 'note_id is required');

        }else {
            try{
                $ret = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $request->note_id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'note must exist');
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
            $date = date('Y-m-d H:i:s');

            try{

                $note = Note::find($request->note_id);

                $note->subject_description = $request->subject_description;
                $note->updated_at = $date;

                $note->save();

            }
            catch (Exception $e){
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Normative subject description update',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            return response(json_encode($note, JSON_UNESCAPED_UNICODE), 200);
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
                try{
                    $reqParam = DB::table('notes')
                        ->join('plans', 'plans.id','notes.plan_id')
                        ->join('groups', 'groups.id','plans.group_id')
                        ->join('departments', 'departments.id','groups.department_id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['notes.id', $request->note_id],
                            ['notes.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }

                $flag = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    }
                }

                if($flag === true){
                    $date = date('Y-m-d H:i:s');

                    try{

                        $note = Note::find($request->note_id);

                        $note->subject_description = $request->subject_description;
                        $note->updated_at = $date;

                        $note->save();

                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Normative subject description update',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    return response(json_encode($note, JSON_UNESCAPED_UNICODE), 200);

                }else{
                    return response('forbidden', 403);
                }
            }else{
                return response('forbidden', 403);
            }
        }

    }

    public function updateSubjectDifficult(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if($request->subject_difficult === null){
            array_push($err, 'subject_difficult is required');
        }
        if($request->note_id === null){
            array_push($err, 'note_id is required');

        }else {
            try{
                $ret = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $request->note_id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'note must exist');
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
            $date = date('Y-m-d H:i:s');

            try{

                $note = Note::find($request->note_id);

                $note->difficult = $request->subject_difficult;
                $note->updated_at = $date;

                $note->save();

            }
            catch (Exception $e){
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Normative subject difficult update',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }

            return response(json_encode($note, JSON_UNESCAPED_UNICODE), 200);
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
                try{
                    $reqParam = DB::table('notes')
                        ->join('plans', 'plans.id','notes.plan_id')
                        ->join('groups', 'groups.id','plans.group_id')
                        ->join('departments', 'departments.id','groups.department_id')
                        ->select('departments.faculty_id', 'groups.department_id')->where([
                            ['notes.id', $request->note_id],
                            ['notes.hidden', 0]
                        ])->first();
                }
                catch (Exception $e){
                    return response($e, 500);
                }

                $flag = false;

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if (intval($userFaculty->faculty_id) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->faculty_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        } else {
                            if (intval($item->scope) === intval($reqParam->department_id)) {
                                $flag = true;
                                break;
                            }

                        }
                    }
                }

                if($flag === true){
                    $date = date('Y-m-d H:i:s');

                    try{

                        $note = Note::find($request->note_id);

                        $note->difficult = $request->subject_difficult;
                        $note->updated_at = $date;

                        $note->save();

                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'Normative subject difficult update',
                            'updated_at' => $date,
                            'created_at' => $date
                        ]);
                    }
                    catch (Exception $e){
                        return response($e, 500);
                    }

                    return response(json_encode($note, JSON_UNESCAPED_UNICODE), 200);

                }else{
                    return response('forbidden', 403);
                }
            }else{
                return response('forbidden', 403);
            }
        }
    }


    private  function create_note($request){
        $date = date('Y-m-d H:i:s');
        $ret = Note::create(
            [
                'hours' => $request->hours,
                'semester' => $request->semester,
                'plan_id' => $request->plan_id,
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
        if($request->hours === null){
            array_push($err, 'hours is required');
        }
        if($request->semester === null){
            array_push($err, 'semester is required');
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





        if($user->id === 1){
            $ret = NoteController::create_note($request);
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

        }else{
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 42],
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
                    ->join('plans', 'plans.group_id', 'groups.id')
                    ->select('departments.faculty_id', 'groups.department_id')->where([
                        ['plans.id', $request->plan_id],
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
                            if(intval($user->department_id) === intval($facultyReq->department_id)){
                                $flag = true;
                                break;
                            }
                        }else {
                            if(intval($item->scope) === intval($facultyReq->department_id)){
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if($flag){
                    $ret = NoteController::create_note($request);
                    return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
                }else{
                    return response('forbidden', 403);
                }
            }else{
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

        if($user->id === 1){
            try {
                $ret = DB::table('notes')
                    ->join('plans', 'plans.id', 'notes.plan_id')
                    ->select('notes.id', 'notes.hours', 'notes.semester', 'notes.plan_id', 'plans.title as plan_title')->where([
                        ['notes.plan_id', $request->plan_id],
                        ['notes.hidden', 0]
                    ])->get();
            }catch (Exception $e){
                return response($e, 500);
            }
            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

        }else{
            try{
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 41],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if(count($ret)>0) {
                $facultyReq = DB::table('departments')
                    ->join('groups', 'groups.department_id', '=', 'departments.id')
                    ->join('plans', 'plans.group_id', 'groups.id')
                    ->select('departments.faculty_id', 'groups.department_id')->where([
                        ['plans.id', $request->plan_id],
                    ])->first();
                $faculty = DB::table('departments')
                    ->select('departments.faculty_id')->where([
                        ['departments.id', $user->department_id],
                    ])->first();
                $notes = [];
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){
                            if(intval($faculty->faculty_id) === intval($facultyReq->faculty_id)){
                                $ret = DB::table('notes')
                                    ->join('plans', 'plans.id', 'notes.plan_id')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('notes.id', 'notes.hours', 'notes.semester', 'notes.plan_id', 'plans.title as plan_title')->where([
                                        ['notes.plan_id', $request->plan_id],
                                        ['departments.faculty_id', intval($facultyReq->faculty_id)],
                                        ['notes.hidden', 0]
                                    ])->get();
                                array_push($notes, $ret);
                                continue;
                            }
                        }else {
                            if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                $ret = DB::table('notes')
                                    ->join('plans', 'plans.id', 'notes.plan_id')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('notes.id', 'notes.hours', 'notes.semester', 'notes.plan_id', 'plans.title as plan_title')->where([
                                        ['notes.plan_id', $request->plan_id],
                                        ['departments.faculty_id', intval($facultyReq->faculty_id)],
                                        ['notes.hidden', 0]
                                    ])->get();
                                array_push($notes, $ret);
                                continue;
                            }
                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($facultyReq->department_id)){
                                $ret = DB::table('notes')
                                    ->join('plans', 'plans.id', 'notes.plan_id')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('notes.id', 'notes.hours', 'notes.semester', 'notes.plan_id', 'plans.title as plan_title')->where([
                                        ['notes.plan_id', $request->plan_id],
                                        ['groups.department_id', intval($facultyReq->department_id)],
                                        ['notes.hidden', 0]
                                    ])->get();
                                array_push($notes, $ret);
                                continue;
                            }
                        }else {
                            if(intval($item->scope) === intval($facultyReq->department_id)){
                                $ret = DB::table('notes')
                                    ->join('plans', 'plans.id', 'notes.plan_id')
                                    ->join('groups', 'groups.id', '=', 'plans.group_id')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('notes.id', 'notes.hours', 'notes.semester', 'notes.plan_id', 'plans.title as plan_title')->where([
                                        ['notes.plan_id', $request->plan_id],
                                        ['groups.department_id', intval($facultyReq->department_id)],
                                        ['notes.hidden', 0]
                                    ])->get();
                                array_push($notes, $ret);
                                continue;
                            }
                        }
                    }
                }
                return response(json_encode(Normalize::normalize($notes), JSON_UNESCAPED_UNICODE), 200);
            }else{
                return response('forbidden', 403);
            }
        }
    }

    private function update_note($request){
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('notes')
                ->where('notes.id', $request->note_id)
                ->update(
                    [
                        'hours' => $request->hours,
                        'semester' => $request->semester,
                        'plan_id' => $request->plan_id,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            return 'err';
        }
        try {
            $ret = DB::table('notes')
                ->join('plans', 'plans.id', 'notes.plan_id')
                ->select('notes.id', 'notes.hours', 'notes.semester', 'notes.plan_id', 'plans.title as plan_title')->where([
                    ['notes.id', $request->note_id],
                    ['notes.hidden', 0]
                ])->first();
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
        if($request->hours === null){
            array_push($err, 'hours is required');
        }
        if($request->semester === null){
            array_push($err, 'semester is required');
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
        if($request->note_id === null){
            array_push($err, 'note_id is required');

        }else {
            try{
                $ret = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $request->note_id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'note must exist');
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
            $ret = NoteController::update_note($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 43],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {
                $flagFrom = false;
                $flagTo = false;



                $facultyReq = DB::table('departments')
                    ->join('groups', 'groups.department_id', '=', 'departments.id')
                    ->join('plans', 'plans.group_id', 'groups.id')
                    ->select('departments.faculty_id', 'groups.department_id')->where([
                        ['plans.id', $request->plan_id],
                    ])->first();
                $faculty = DB::table('departments')
                    ->select('departments.faculty_id')->where([
                        ['departments.id', $user->department_id],
                    ])->first();

                $noteOld =  DB::table('notes')
                    ->join('plans', 'plans.id', 'notes.plan_id')
                    ->join('groups', 'groups.id', 'plans.group_id')
                    ->join('departments', 'departments.id', 'groups.department_id')
                    ->select('notes.id',  'plans.group_id', 'groups.department_id', 'departments.faculty_id')->where('notes.id', $request->note_id)->first();



                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {

                            if (intval($faculty->faculty_id) === intval($facultyReq->faculty_id)) {
                                $flagTo = true;
                            }
                            if (intval($faculty->faculty_id) === intval($noteOld->faculty_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($facultyReq->faculty_id)) {
                                $flagTo = true;
                            }
                            if (intval($item->scope) === intval($noteOld->faculty_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if (intval($user->department_id) === intval($facultyReq->department_id)) {
                                $flagTo = true;
                            }
                            if (intval($user->department_id) === intval($noteOld->department_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if (intval($item->scope) === intval($facultyReq->department_id)) {
                                $flagTo = true;
                            }
                            if (intval($item->scope) === intval($noteOld->department_id)) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    }
                }


                if ($flagFrom && $flagTo) {
                    $ret = NoteController::update_note($request);
                    if ($ret === 'err') {
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    } else {
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

    function delete_note($request){
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('notes')
                ->where('notes.id', $request->note_id)
                ->update(
                    [
                        'hidden' => true,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            return 'err';
        }
        return 'Delete OK';
    }

    public function delete(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if($request->note_id === null){
            array_push($err, 'note_id is required');

        }else {
            try{
                $ret = DB::table('notes')
                    ->select('notes.id')->where([
                        ['notes.id', $request->note_id],
                        ['notes.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($ret === null){
                array_push($err, 'note must exist');
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
            $ret = NoteController::delete_note($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 44],
                        ['possibility_has_roles.hidden', 0]
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if (count($ret) > 0) {

                $flag= false;

                $noteOld =  DB::table('notes')
                    ->join('plans', 'plans.id', 'notes.plan_id')
                    ->join('groups', 'groups.id', 'plans.group_id')
                    ->join('departments', 'departments.id', 'groups.department_id')
                    ->select('notes.id',  'plans.group_id', 'groups.department_id', 'departments.faculty_id')->where('notes.id', $request->note_id)->first();


                $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();
                foreach ($ret as $item){
                    if($item->type === 'faculty'){
                        if($item->scope === 'own'){

                            if(intval($faculty->faculty_id) === intval($noteOld->faculty_id)){
                                $flag = true;
                                break;
                            }

                        }else {
                            if(intval($item->scope) === intval($noteOld->faculty_id)){
                                $flag = true;
                                break;
                            }

                        }
                    }else if($item->type === 'department'){
                        if($item->scope === 'own'){
                            if(intval($user->department_id) === intval($noteOld->department_id)){
                                $flag = true;
                                break;
                            }

                        }else {
                            if(intval($item->scope)  === intval($noteOld->department_id)){
                                $flag = true;
                                break;
                            }

                        }
                    }
                }
                if($flag){
                    $ret =  NoteController::delete_note($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
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
