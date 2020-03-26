<?php

namespace App\Http\Controllers;

use App\Group;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

class GroupController extends Controller
{
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

        function create_group($request){
            $date = date('Y-m-d H:i:s');
            $ret = Group::create(
                [
                    'code' => $request->code,
                    'department_id' => $request->department_id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            return $ret;
        }


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = create_group($request);
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
                    $ret = create_group($request);
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
                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                            ['groups.hidden', 0],
                            ['departments.hidden', 0],
                            ['groups.department_id', $request->department_id],
                        ])->get();
                }
                catch (Exception $e){
                    return response('server error', 500);
                }
            }else{
                try{
                    $ret =  DB::table('groups')
                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                            ['groups.hidden', 0],
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
                                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.faculty_id', intval($faculty->faculty_id)],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($groups, $ret);
                                }
                            }else {
                                if(intval($item->scope) === intval($facultyReq->faculty_id)){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.faculty_id', intval($item->scope)],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($groups, $ret);
                                }
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                if(intval($user->department_id) === intval($request->department_id)){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($groups, $ret);
                                }
                            }else {
                                if(intval($item->scope) === intval($request->department_id)){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', $request->department_id],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
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
                                    ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', intval($faculty->faculty_id)],
                                        ['departments.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                array_push($groups, $ret);

                            }else {
                                $ret = DB::table('groups')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                        ['departments.faculty_id', intval($item->scope)],
                                        ['departments.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
                                array_push($groups, $ret);
                            }
                        }else if($item->type === 'department'){
                            if($item->scope === 'own'){
                                    $ret =  DB::table('groups')
                                        ->join('departments', 'departments.id', '=', 'groups.department_id')
                                        ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                            ['groups.department_id', intval($user->department_id)],
                                            ['departments.hidden', 0],
                                            ['groups.hidden', 0],
                                        ])->get();
                                    array_push($groups, $ret);
                            }else {
                                $ret = DB::table('groups')
                                    ->join('departments', 'departments.id', '=', 'groups.department_id')
                                    ->select('groups.id', 'groups.code', 'groups.department_id', 'departments.title as department_title')->where([
                                        ['groups.department_id', intval($item->scope)],
                                        ['departments.hidden', 0],
                                        ['groups.hidden', 0],
                                    ])->get();
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

        function update_group($request){
            $date = date('Y-m-d H:i:s');
            try {
                DB::table('groups')
                    ->where('groups.id', $request->group_id)
                    ->update(
                        [
                            'code' => $request->code,
                            'department_id' => $request->department_id,
                            'updated_at' => $date,
                        ]
                    );
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


        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = update_group($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
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
                    $ret = update_group($request);
                    if($ret === 'err'){
                        return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
                    }else{
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

        function delete_group($request){
            $date = date('Y-m-d H:i:s');
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
                return 'err';
            }
            return 'Delete OK';
        }

        if($user->id === 1){  //Если суперюзер то сразу выполняем
            $ret = delete_group($request);
            if($ret === 'err'){
                return response(json_encode('server error', JSON_UNESCAPED_UNICODE), 500);
            }else{
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
                    $ret = delete_group($request);
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
