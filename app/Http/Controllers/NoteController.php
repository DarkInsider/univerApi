<?php

namespace App\Http\Controllers;

use App\Note;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

class NoteController extends Controller
{
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


        function create_note($request){
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


        if($user->id === 1){
            $ret = create_note($request);
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
                    $ret = create_note($request);
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

        function update_note($request){
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = update_note($request);
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
                    $ret = update_note($request);
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

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = delete_note($request);
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
                    $ret = delete_note($request);
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
