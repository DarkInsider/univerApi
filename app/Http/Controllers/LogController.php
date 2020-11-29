<?php

namespace App\Http\Controllers;

use App\Http\Helpers\GetUser;
use App\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function get(Request $request){
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


        if ($user->id === 1) {
            try {

                $ret = DB::table('logs')
                    ->join('users', 'users.id', 'logs.user_id')
                    ->select('logs.id', 'logs.action', 'logs.user_id', 'logs.action', 'logs.created_at', 'users.login')->where([
                        ['logs.hidden', 0]
                    ])->paginate(10);


            } catch (Exception $e) {
                return response($e, 500);
            }

            return response(json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
        } else {
            return response(json_encode('forbidden', JSON_UNESCAPED_UNICODE), 403);
        }

    }
}
