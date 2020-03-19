<?php

namespace App\Http\Controllers;

use App\Faculty;
use Illuminate\Http\Request\FacultyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


function getUser($token){
    try{
        $user = DB::table('users')->where('token', $token)->first();
    }catch (Exception $e){
        return 'err';
    }
    return $user;
}

class FacultyController extends Controller
{



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->token !== null){
            $user = getUser($request->token);
            if($user === 'err'){
                return response('server error', 500);
            }
            if($user === null){
                return response('unauthorized', 401);
            }
            if($user->id === 1){
                return Faculty::all();
            }

            else{
                return response('forbidden', 403);
            }
        }else{
            return response('unauthorized', 401);
        }

    }


    public function create(Request $request)
    {
        //requests
        $err=[];
        if($request->token === null){
            array_push($err, 'token is required');
        }
        if($request->title === null){
            array_push($err, 'title is required');
        }

        if(count($err) > 0){
            return response($err, 400);
        }

            $user = getUser($request->token);
            if($user === 'err'){
                return response('server error', 500);
            }
            if($user === null){
                return response('unauthorized', 401);
            }
            if($user->id === 1){
                $date = date('Y-m-d H:i:s');
              $ret =  Faculty::create([
                  'title' => $request->title,
                  'created_at' => $date,
                  'updated_at' => $date,
                ]);

                return response(  json_encode($ret, JSON_UNESCAPED_UNICODE), 200);
            }

            else{
                return response('forbidden', 403);
            }


    }
	
	public function delete(Request $request)
    {
        //requests
        $err=[];
        if($request->token === null){
            array_push($err, 'token is required');
        }
        if($request->id === null){
            array_push($err, 'id is required');
        }

        if(count($err) > 0){
            return response($err, 400);
        }

            $user = getUser($request->token);
            if($user === 'err'){
                return response('server error', 500);
            }
            if($user === null){
                return response('unauthorized', 401);
            }
            if($user->id === 1){
                try {
			DB::table('faculties')
			->where('id', $request->id)
			->update(['hidden' => true]);
		}
		catch(Exception $e) {
			return response($e, 500);
		}

                return response(  json_encode('succes', JSON_UNESCAPED_UNICODE), 200);
            }

            else{
                return response('forbidden', 403);
            }


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FacultyRequest $request)
    {
        $ret = Faculty::create($request->validated());
        return $ret;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Faculty  $faculty
     * @return \Illuminate\Http\Response
     */
    public function show(Faculty $faculty)
    {
        return $faculty = Faculty::findOrFail($faculty);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Faculty  $faculty
     * @return \Illuminate\Http\Response
     */


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Faculty  $faculty
     * @return \Illuminate\Http\Response
     */
    public function update(FacultyRequest $request, $id)
    {
         $faculty = Faculty::findOrFail($id);
         $faculty->fill($request->except(['faculty_id']));
         $faculty->save();
         return response()->json($faculty);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Faculty  $faculty
     * @return \Illuminate\Http\Response
     */
    public function destroy(FacultyRequest $request, $id)
    {
        $faculty = Faculty::findOrFail($id);
        if($faculty->delete()) return response(null, 204);
    }
}
