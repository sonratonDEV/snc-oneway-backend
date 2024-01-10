<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    function employeessignIn(Request $request){
        try{

            return response() -> json([
                'status' => 'success',
                'message' =>  'Login successfully',
                'data' => ["request" => $request->all()]
            ]);
        }catch(\Exception $e){
            return response() -> json([
                'status' => 'error',
                'message' =>  $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
