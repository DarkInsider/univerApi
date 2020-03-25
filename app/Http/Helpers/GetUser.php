<?php
namespace App\Http\Helpers;
use Illuminate\Support\Facades\DB;


class GetUser {
    public static function get($token){
        try{
            $user = DB::table('users')->where([['token', $token],['hidden', 0]])
                ->orWhere([['token', $token], ['id', 1]])
                ->first();
        }catch (Exception $e){
            return 'err';
        }
        return $user;
    }
}

