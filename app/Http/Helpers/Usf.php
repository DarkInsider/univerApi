<?php
namespace App\Http\Helpers;
use App\Choise;
use Illuminate\Support\Facades\DB;

class Usf {
    public static function create_choise($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        DB::beginTransaction();
        $rets = [];
        foreach ($request->subject_ids as $subject){
            try {
                $ret =Choise::create (
                    [
                        'date' => $date,
                        'subject_id' => intval($subject) ,
                        'student_id' => intval($request->student_id),
                        'updated_at' => $date,
                        'created_at'=> $date
                    ]
                );
            } catch (\Exception $e) {
                $response['code'] = 500;
                $response['message'] = 'Server Error';
                $response['data'] = $e;
                DB::rollback();
                return $response;
            }
            array_push($rets, $ret);
        }

        $response['code'] = 200;
        $response['message'] = 'OK';
        $response['data'] = $rets;
        DB::commit();
        return $response;
    }
    public static function create_subject($request){
        $date = date('Y-m-d H:i:s');
        $response = [];
        try {
            $ret =Subject::create (
                [
                    'lecturer_id' => $request->lecturer_id,
                    'department_id' => $request->department_id,
                    'type' => $request->type,
                    'title' => $request->title,
                    'hours' => $request->hours,
                    'html' => $request->html,
                    'updated_at' => $date,
                    'created_at'=> $date
                ]
            );
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

}
