<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ToDoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleAccessController;

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

Route::prefix('main-oneway') -> controller(MainCategoryController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll');
    Route::put('/update','update');
    Route::delete("/delete", "delete");
});

Route::prefix('sub-oneway') -> controller(SubCategoryController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll');
    Route::put('/update','update');
    Route::delete("/delete", "delete");
});

Route::prefix('service-oneway') -> controller(ServiceCategoryController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll');
    Route::put('/update','update');
    Route::delete("/delete", "delete");
    Route::patch("/enable-disable", "enableAndDisable");
});

Route::prefix('eventcat-oneway') -> controller(EventCategoryController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll'); //Route::get("/categories", "eventCategories");
    Route::put('/update','update');
    Route::delete("/delete", "delete");
});
Route::prefix('event-oneway') -> controller(EventController::class) -> group(function () {
    Route::post('/create','create'); //Route::post("/", "create");
    Route::get('/pending-approval','pendingApprovals'); //Route::get("/pending-approvals", "pendingApprovals");
    Route::get('/events','events'); //Route::get("/events", "events");
    Route::get("/event-count", "eventCount");
    Route::put('/update','update'); //Route::put('/','update');
    Route::delete("/delete", "delete"); //Route::delete("/", "delete");
    Route::patch("/approve", "approve");
    Route::patch("/disapprove", "disapprove");
});

Route::prefix('todo-oneway') -> controller(ToDoController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll');
    Route::put('/update','update');
    Route::delete("/delete", "delete");
});

Route::prefix('role-oneway') -> controller(RoleController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll');
    Route::put('/update','update');
    Route::delete("/delete", "delete");
});

Route::prefix('access-oneway') -> controller(RoleAccessController::class) -> group(function () {
    Route::post('/create','create');
    Route::get('/get-all','getAll');
    Route::put('/update','update');
    Route::delete("/delete", "delete");
});
