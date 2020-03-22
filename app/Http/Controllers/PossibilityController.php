<?php

namespace App\Http\Controllers;

use App\Possibility;
use Illuminate\Http\Request;

use App\Http\Helpers\GetUser;
use App\Http\Helpers\Normalize;

use Illuminate\Support\Facades\DB;

class PossibilityController extends Controller
{
    public function get(Request $request){
        //requests
        $err=[];
        if($request->header('token') === null){
            array_push($err, 'token is required');
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
            $ret =  DB::table('possibilities')
                ->select('id', 'title')->where([
                    ['possibilities.hidden', 0],
                ])->get();
            return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        }else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }
    }
}
