<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MainCategoryController;

// test main route
Route::get('/', function(){
    return response() -> json([
        'status' => 'success',
        'message' => 'Welcome to SNC One Way API.',
        'data' => [],
    ]);
});

// test database connection
Route::get('/pg-connect', function(){
    try{
        $result = DB::table('employees')->select('*')->get();
        return response() -> json([
            'result' => $result,
        ]);
    }catch(\Exception $e){
        return response() -> json([
            'result' =>  $e->getMessage(),
        ]);
    }
});

// employees controller
Route::prefix('snc-oneway') -> controller(EmployeeController::class) -> group(function () {
    Route::post('/signin','employeeSignIn');
    Route::post('/create','create');
});

Route::prefix('snc-oneway') -> controller(MainCategoryController::class) -> group(function () {
    Route::post('/create','create');
});

