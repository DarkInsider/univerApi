<?php

namespace App\Http\Controllers;
use App\Log;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\User;
use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;



class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::all();
    }
    public function block()
    {
        return 'Method not allowed';
    }

    public function login(Request $request)
    {
        $err=[];
        if($request->login === null){
            array_push($err, 'login is required');
        }
        if($request->password === null){
            array_push($err, 'password is required');
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $user = DB::table('users')
                ->where([['login', $request->login], ['password', md5($request->password)], ['hidden', 0]])
                ->orWhere([['login', $request->login], ['password', md5($request->password)], ['id', 1]])
                ->first();
        }
        catch (Exception $e){
            return response($e, 500);
        }

        if($user !== null){
            $token = 'tok '.md5(''.$user->name.rand(0,100000));

            try{
                  DB::table('users')
                    ->where('id', $user->id)
                    ->update(['token' => $token]);
            }
            catch (Exception $e){
                return response($e, 500);
            }
            try{
                $user = DB::table('users')
                    ->join('roles', 'roles.id', '=', 'users.role_id')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.id', 'users.name', 'users.email', 'users.token', 'users.role_id', 'roles.title as role_name', 'users.department_id', 'departments.title as department_name')->where('users.id', $user->id)->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'User login',
                    'updated_at' => $date,
                    'created_at' => $date
                ]);
            }
            catch (Exception $e){
                return response($e, 500);
            }


            return response(json_encode($user), 200);
        }else{
            return response(json_encode($user), 404);
        }


    }


    public function logout(Request $request)
    {
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $user = DB::table('users')
                ->select()
                ->where([['users.token', $request->header('token')],['users.hidden',0]])
                ->first();

        }
        catch (Exception $e){
            return response(  json_encode('Server error', JSON_UNESCAPED_UNICODE), 500);
        }

        try{
            $date = date('Y-m-d H:i:s');
            DB::table('users')
                ->where('users.token', $request->header('token'))
                ->update([
                    'token' => null,
                    'updated_at' => $date,
                ]);
        }
        catch (Exception $e){
            return response(  json_encode('Server error', JSON_UNESCAPED_UNICODE), 500);
        }
        $date = date('Y-m-d H:i:s');
        try{
            Log::create([
                'user_id' => $user->id,
                'action' => 'User logout',
                'updated_at' => $date,
                'created_at' => $date
            ]);
        }
        catch (Exception $e){
            return response($e, 500);
        }
        return response(  json_encode('Logout OK', JSON_UNESCAPED_UNICODE), 200);
    }


    public function getUserInfoByToken(Request $request)
    {
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }else {
            try{
                $user =   DB::table('users')
                    ->select()
                    ->where('token',$request->header('token'))
                    ->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($user === null){
                array_push($err, 'bad token');
            }
        }
        if(count($err) > 0){
            return response($err, 400);
        }

        try{
            $user = DB::table('users')
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->join('departments', 'departments.id', '=', 'users.department_id')
                ->select('users.id', 'users.login', 'users.name', 'users.email', 'users.token', 'users.role_id', 'roles.title as role_title', 'users.department_id', 'departments.title as department_title')->where('users.token', $request->header('token'))->first();
        }
        catch (Exception $e){
            return response(  json_encode('Server error', JSON_UNESCAPED_UNICODE), 500);
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
        if($ret3 != null) {
            $user->isStudent = true;
//            $user->student_id = $ret3->id;
//            try {
//                $ret2 = DB::table('groups')
//                    ->join('plans', 'groups.id', '=', 'plans.group_id')
//                    ->select('plans.id as plan_id', 'groups.semester')->where([
//                        ['groups.id', $ret3->group_id],
//                        ['plans.active', 1],
//                        ['plans.hidden', 0]
//                    ])->first();
//            } catch (Exception $e) {
//                return response($e, 500);
//            }
//
//            if ($ret2 != null) {
//                    try {
//                        $tmp = DB::table('notes')
//                            ->select(DB::raw('SUM(credits_ECTS) as total_credits_ECTS'))->where([
//                                ['notes.plan_id', $ret2->plan_id],
//                                ['notes.semester', $ret2->semester],
//                                ['notes.type', 'V'],
//                                ['notes.hidden', 0]
//                            ])
//                            ->groupBy('semester')
//                            ->first();
//                    } catch (Exception $e) {
//                        return response($e, 500);
//                    }
//                    if($tmp !== null){
//                        $user->hours = $tmp->total_credits_ECTS;
//                    }else{
//                        $user->hours = 0;
//                    }
//
//
//                $creditsSum = 0;
//
//                try {
//                    $tmp = DB::table('choises')
//                        ->select('choises.subject_id','choises.subject_type')->where([
//                            ['choises.student_id', $ret3->id],
//                            ['choises.hidden', 0]
//                        ])
//                        ->get();
//                } catch (Exception $e) {
//                    return response($e, 500);
//                }
//                if(count($tmp) > 0) {
//                    foreach ($tmp as $item) {
//                        if ($item->subject_type === 'V') {
//                            try {
//                                $sub1 = DB::table('subjects')
//                                    ->select('subjects.id', 'subjects.credits_ECTS')->where([
//                                        ['subjects.id', $item->subject_id],
//                                        ['subjects.hidden', 0],
//                                        ['subjects.active', 1]
//                                    ])->first();
//                            } catch (Exception $e) {
//                                return response($e, 500);
//                            }
//                            if ($sub1 === null) {
//
//                            } else {
//                                $creditsSum += $sub1->credits_ECTS;
//                            }
//                        } else if ($item->subject_type === 'N') {
//                            try {
//                                $sub2 = DB::table('notes')
//                                    ->select('notes.id', 'notes.credits_ECTS')->where([
//                                        ['notes.id', $item->subject_id],
//                                        ['notes.hidden', 0]
//                                    ])->first();
//                            } catch (Exception $e) {
//                                return response($e, 500);
//                            }
//                            if ($sub2 === null) {
//                            } else {
//                                $creditsSum += $sub2->credits_ECTS;
//                            }
//                        }
//                    }
//                }
//                $user->hours_selected = $creditsSum;
//
////                    try {
////                        $tmp = DB::table('choises')
////                            ->join('subjects', 'subjects.id', '=', 'choises.subject_id')
////                            ->select(DB::raw('SUM(subjects.hours) as total_hours'))->where([
////                                ['choises.student_id', $ret3->id],
////                                ['choises.hidden', 0]
////                            ])
////                            ->groupBy('choises.student_id')
////                            ->first();
////                    } catch (Exception $e) {
////                        return response($e, 500);
////                    }
////                    if ($tmp !== null) {
////                        $user->hours_selected = $tmp->total_hours;
////                    } else {
////                        $user->hours_selected = 0;
////                    }
//            } else {
//
//            }
        }else {
            $user->isStudent = false;
        }

        return response(  json_encode($user, JSON_UNESCAPED_UNICODE), 200);
    }


    private function create_user($request){
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
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
        }
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
        if($request->role_id === null){
            array_push($err, 'role_id is required');

        }else{
            try{
                $role = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0]
                    ])->first();
            }
            catch (Exception $e){
                return response($e, 500);
            }
            if($role === null){
                array_push($err, 'role must exist');
            }
        }
        if($request->department_id === null){
            array_push($err, 'department_id is required');

        }else{
            try{
                $role = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0]
                    ])->first();
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




        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = UserController::create_user($request);

            $date = date('Y-m-d H:i:s');
            try{
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'User create',
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
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 14],
                        ['role_has_roles.role_id_has', $request->role_id],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0]
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
                    $ret =  UserController::create_user($request);
                    $date = date('Y-m-d H:i:s');
                    try{
                        Log::create([
                            'user_id' => $user->id,
                            'action' => 'User create',
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
        $err = [];
        if ($request->header('token') === null) {
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
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
        }

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            if($request->department_id !== null){
                try{
                    $ret =  DB::table('users')
                        ->join('departments', 'departments.id', '=', 'users.department_id')
                        ->join('roles', 'roles.id', '=', 'users.role_id')
                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                            ['users.hidden', 0],
                            ['roles.hidden', 0],
                            ['departments.hidden', 0],
                            ['users.department_id', $request->department_id],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
            }else{
                try{
                    $ret =  DB::table('users')
                        ->join('departments', 'departments.id', '=', 'users.department_id')
                        ->join('roles', 'roles.id', '=', 'users.role_id')
                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                            ['users.hidden', 0],
                            ['roles.hidden', 0],
                            ['departments.hidden', 0],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
            }
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            try {
                $ret = DB::table('possibility_has_roles')
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 13],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

           // return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

            if(count($ret)>0) {
                $users = [];
                if($request->department_id !== null){
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
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['departments.faculty_id', intval($facultyReq->faculty_id)],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }else {
                                if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['departments.faculty_id', intval($facultyReq->faculty_id)],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                if(intval($user->department_id) === intval($request->department_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }else {
                                if(intval($item->scope) === intval($request->department_id)){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', $request->department_id]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                                }
                            }
                        }
                    }
                }else{
                    $faculty = DB::table('departments')->select('departments.faculty_id')->where([
                       ['departments.id', $user->department_id],
                    ])->first();
                    foreach ($ret as $item){
                        if($item->type === 'faculty'){
                            if($item->scope === 'own'){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['departments.faculty_id', intval($faculty->faculty_id)],
                                            ['users.role_id', intval($item->role_id_has)],
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }else {
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['users.role_id', intval($item->role_id_has)],
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', intval($user->department_id)]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }else {
                                    $ret =  DB::table('users')
                                        ->join('departments', 'departments.id', '=', 'users.department_id')
                                        ->join('roles', 'roles.id', '=', 'users.role_id')
                                        ->select('users.id', 'users.name', 'users.login', 'users.department_id', 'departments.title as department_title', 'users.role_id', 'roles.title as role_title')->where([
                                            ['users.hidden', 0],
                                            ['roles.hidden', 0],
                                            ['departments.hidden', 0],
                                            ['users.role_id', intval($item->role_id_has)],
                                            ['users.department_id', intval($item->scope)]
                                        ])->get();
                                    array_push($users, $ret);
                                    continue;
                            }
                        }
                    }
                }
                return response(  json_encode(Normalize::normalize($users), JSON_UNESCAPED_UNICODE), 200);

            }else{
                return response('forbidden', 403);
            }
        }


    }



    private function update_user($request){
        $date = date('Y-m-d H:i:s');
        try {


            if($request->password !== null){
                DB::table('users')
                    ->where('users.id', $request->user_id)
                    ->update(
                        [
                            'name' => $request->name,
                            'login' => $request->login,
                            'password' => md5($request->password),
                            'role_id' => $request->role_id,
                            'department_id' => $request->department_id,
                            'updated_at' => $date,
                        ]
                    );
            }else{
                DB::table('users')
                    ->where('users.id', $request->user_id)
                    ->update(
                        [
                            'name' => $request->name,
                            'login' => $request->login,
                            'role_id' => $request->role_id,
                            'department_id' => $request->department_id,
                            'updated_at' => $date,
                        ]
                    );
            }

        } catch (Exception $e) {
            return 'err';
        }
        try {
            $ret = DB::table('users')
                ->select('users.id', 'users.name', 'users.login', 'users.role_id', 'users.department_id')->where('users.id', $request->user_id)->first();
        } catch (Exception $e) {
            return 'err';
        }
        return $ret;
    }


    public function update(Request $request)
    {
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->name === null) {
            array_push($err, 'name is required');
        }
        if ($request->user_id === null) {
            array_push($err, 'user_id is required');

        } else {
            try {
                $ret = DB::table('users')
                    ->select('users.id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0],
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'user must exist');
            }else{
                if ($request->login === null) {
                    array_push($err, 'login is required');

                } else {
                    try {
                        $ret = DB::table('users')
                            ->select('users.login')->where([['users.login', $request->login],['users.id', '!=', $request->user_id]])->first();
                    } catch (Exception $e) {
                        return response($e, 500);
                    }
                    if ($ret !== null) {
                        array_push($err, 'login must be unique');
                    }
                }
            }
        }
        if ($request->role_id === null) {
            array_push($err, 'role_id is required');

        } else {
            try {
                $ret = DB::table('roles')
                    ->select('roles.id')->where([
                        ['roles.id', $request->role_id],
                        ['roles.hidden', 0],
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'role must exist');
            }
        }
        if ($request->department_id === null) {
            array_push($err, 'department_id is required');

        } else {
            try {
                $ret = DB::table('departments')
                    ->select('departments.id')->where([
                        ['departments.id', $request->department_id],
                        ['departments.hidden', 0],
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'department must exist');
            }
        }
        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
        }

        if($user->id === 1){
            $ret =  UserController::update_user($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'User update',
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
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 15],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

            //return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

            if(count($ret)>0) {


                $flagFrom = false;
                $flagTo = false;


                $userPrev = DB::table('users')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.id', 'users.name', 'users.login', 'users.role_id', 'users.department_id', 'departments.faculty_id')->where('users.id', $request->user_id)->first();

                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                $facultyReq = DB::table('departments')
                    ->select('departments.id', 'departments.faculty_id')->where([
                        ['departments.id', $request->department_id],
                    ])->first();

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if ((intval($userFaculty->faculty_id) === intval($facultyReq->faculty_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($userFaculty->faculty_id) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if ((intval($item->scope) === intval($facultyReq->faculty_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($item->scope) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if ((intval($user->department_id) === intval($request->department_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($user->department_id) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        } else {
                            if ((intval($item->scope) === intval($request->department_id)) && (intval($request->role_id) === intval($item->role_id_has))) {
                                $flagTo = true;
                            }
                            if ((intval($item->scope) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flagFrom = true;
                            }
                            continue;
                        }
                    }
                }
                if ($flagFrom && $flagTo) {
                    $ret =  UserController::update_user($request);
                    if ($ret === 'err') {
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    } else {
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'User update',
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
            }else{
                return response('forbidden', 403);
            }


        }


    }

    private   function delete_user($request){
        $date = date('Y-m-d H:i:s');
        try {
            DB::table('users')
                ->where('users.id', $request->user_id)
                ->update(
                    [
                        'hidden' => true,
                        'token' => NULL,
                        'updated_at' => $date,
                    ]
                );
        } catch (Exception $e) {
            return 'err';
        }
        return 'Delete OK';
    }

    public function delete(Request $request){
        //requests
        $err = [];
        if ($request->header('token') === null) {
            array_push($err, 'token is required');
        }
        if ($request->user_id === null) {
            array_push($err, 'user_id is required');

        } else {
            try {
                $ret = DB::table('users')
                    ->select('users.id')->where([
                        ['users.id', $request->user_id],
                        ['users.hidden', 0],
                    ])->first();
            } catch (Exception $e) {
                return response($e, 500);
            }
            if ($ret === null) {
                array_push($err, 'user must exist');
            }
        }
        if (count($err) > 0) {
            return response($err, 400);
        }

        $user = GetUser::get($request->header('token'));
        if($user === 'err'){
            return response('server error', 500);
        }
        if($user === null){
            return response('unauthorized', 401);
        }



        if($user->id === 1){
            $ret =  UserController::delete_user($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
                $date = date('Y-m-d H:i:s');
                try{
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'User delete',
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
                    ->join('role_has_roles', 'possibility_has_roles.role_id', '=', 'role_has_roles.role_id')
                    ->select()->where([
                        ['possibility_has_roles.role_id', $user->role_id],
                        ['possibility_has_roles.possibility_id', 16],
                        ['possibility_has_roles.hidden', 0],
                        ['role_has_roles.hidden', 0],
                    ])->get();
            } catch (Exception $e) {
                return response($e, 500);
            }

            //return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);

            if (count($ret) > 0) {
                $userFaculty = DB::table('departments')->select('departments.faculty_id')->where([
                    ['departments.id', $user->department_id],
                ])->first();

                $userPrev = DB::table('users')
                    ->join('departments', 'departments.id', '=', 'users.department_id')
                    ->select('users.id', 'users.name', 'users.login', 'users.role_id', 'users.department_id', 'departments.faculty_id')->where('users.id', $request->user_id)->first();

                $flag = false;

                foreach ($ret as $item) {
                    if ($item->type === 'faculty') {
                        if ($item->scope === 'own') {
                            if ((intval($userFaculty->faculty_id) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if ((intval($item->scope) === intval($userPrev->faculty_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        }
                    } else if ($item->type === 'department') {
                        if ($item->scope === 'own') {
                            if ((intval($user->department_id) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        } else {
                            if ((intval($item->scope) === intval($userPrev->department_id)) && (intval($userPrev->role_id) === intval($item->role_id_has))) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }

                if ($flag) {
                    $ret =  UserController::delete_user($request);
                    if ($ret === 'err') {
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    } else {
                        $date = date('Y-m-d H:i:s');
                        try{
                            Log::create([
                                'user_id' => $user->id,
                                'action' => 'User delete',
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
