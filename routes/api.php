<?php

use Illuminate\Http\Request;
use Http\Controllers\FacultyController;
use Http\Controllers\UserController;
use Http\Controllers\DepartmentController;
use Http\Controllers\RoleController;
use Http\Controllers\PossibilityController;
use Http\Controllers\PossibilityHasRoleController;
use Http\Controllers\RoleHasRoleController;
use Http\Controllers\PlanController;
use Http\Controllers\NoteController;
use Http\Controllers\SubjectController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/user/login', 'UserController@login');
Route::get('/user/login', 'UserController@block');

Route::get('/user/logout', 'UserController@logout');
Route::get('/user/getInfoByToken', 'UserController@getUserInfoByToken');

Route::post('/user', 'UserController@create');
Route::get('/user', 'UserController@get');
Route::put('/user', 'UserController@update');
Route::delete('/user', 'UserController@delete');

Route::get('/faculty', 'FacultyController@get');
Route::post('/faculty', 'FacultyController@create');
Route::put('/faculty', 'FacultyController@update');
Route::delete('/faculty', 'FacultyController@delete');

Route::post('/faculty/create', 'FacultyController@create');
Route::get('/faculty/create', 'UserController@block');


Route::get('/department', 'DepartmentController@get');
Route::post('/department', 'DepartmentController@create');
Route::put('/department', 'DepartmentController@update');
Route::delete('/department', 'DepartmentController@delete');

Route::get('/role', 'RoleController@get');
Route::post('/role', 'RoleController@create');
Route::put('/role', 'RoleController@update');
Route::delete('/role', 'RoleController@delete');

Route::get('/possibility', 'PossibilityController@get');

Route::get('/possibilityHasRole', 'PossibilityHasRoleController@get');
Route::post('/possibilityHasRole', 'PossibilityHasRoleController@create');
Route::put('/possibilityHasRole', 'PossibilityHasRoleController@update');
Route::delete('/possibilityHasRole', 'PossibilityHasRoleController@delete');

Route::get('/possibilityHasRole/getByToken', 'PossibilityHasRoleController@getPosByToken');


Route::get('/roleHasRole', 'RoleHasRoleController@get');
Route::post('/roleHasRole', 'RoleHasRoleController@create');
Route::put('/roleHasRole', 'RoleHasRoleController@update');
Route::delete('/roleHasRole', 'RoleHasRoleController@delete');

Route::get('/group', 'GroupController@get');
Route::post('/group', 'GroupController@create');
Route::put('/group', 'GroupController@update');
Route::delete('/group', 'GroupController@delete');

Route::get('/plan', 'PlanController@get');
Route::get('/plan/{id}', 'PlanController@getByID');
Route::post('/plan', 'PlanController@create');
Route::put('/plan', 'PlanController@update');
Route::delete('/plan', 'PlanController@delete');

Route::put('/planSetActive', 'PlanController@setActive');


Route::post('/planImport', 'PlanController@import');

Route::get('/note', 'NoteController@get');
Route::post('/note', 'NoteController@create');
Route::put('/note', 'NoteController@update');
Route::delete('/note', 'NoteController@delete');

Route::get('/student', 'StudentController@get');
Route::post('/student', 'StudentController@create');
Route::put('/student', 'StudentController@update');
Route::delete('/student', 'StudentController@delete');

Route::get('/studentsExport', 'StudentController@studentExport');
Route::post('/studentsImport', 'StudentController@import');

Route::post('/lecturer', 'LecturerController@create');
Route::get('/lecturer', 'LecturerController@get');
Route::put('/lecturer', 'LecturerController@update');
Route::delete('/lecturer', 'LecturerController@delete');

Route::post('/pinLecturerToDepartment', 'LecturerController@pinLecturerToDepartment');
Route::put('/pinLecturerToDepartment', 'LecturerController@pinUpdateLecturerToDepartment');
Route::delete('/pinLecturerToDepartment', 'LecturerController@pinDeleteLecturerToDepartment');

Route::post('/subject', 'SubjectController@create');
Route::get('/subject', 'SubjectController@get');
Route::put('/subject', 'SubjectController@update');
Route::delete('/subject', 'SubjectController@delete');

Route::get('/subject/{id}', 'SubjectController@getById');

Route::post('/choise', 'ChoiseController@create');
Route::get('/choise', 'ChoiseController@get');
Route::delete('/choise', 'ChoiseController@subjectClear');

Route::get('/choiseExport', 'ChoiseController@export');
