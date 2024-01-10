<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

use DateTime;

class EmployeeController extends Controller
{
    private $jwtUtils;

    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    function employeeSignIn(Request $request){
        try {
            $rules = [
                "emp_id" => ["required", "numeric", "digits:7"] //ต้องการข้อมูล ตัวเลข 7หลัก เพื่อเช็คในระบบ log-in
                // "emp_id" => "required|string
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator-> fails()) return response()->json([
                "status"    => "error",
                "message"   => "Bad request",
                "data"      => [
                    "validator" => $validator->errors()
                ],


            ],400);

            $empID = $request->emp_id;
            // $result = DB::table('tb_employees')->whereRaw( "emp_id like '%$empID'")->get();
            $result = DB::table('employees')->selectRaw(
                "emp_id
                ,name->>'th' as name_th
                ,name->>'en' as name_en
                ,role"
            )->where("emp_id", "like", "%".$empID)->get();
            if (\count($result) == 0) return response()->json([
                "status"    => "error",
                "message"   => "User does not exits",
                "data"      => [],
            ], 400);

            date_default_timezone_set('Asia/Bangkok');  // JWT
            $now = new DateTime();
            $payload = [
                "emp_id"    => $result[0]->emp_id,
                "role"      => $result[0]->role,
                "iat"       => $now->getTimestamp(),
                "exp"       => $now->modify("+3 hours")->getTimestamp()
            ];

        $token = $this->jwtUtils->generateToken($payload);

            return response() ->json([
                "status" => "success",
                "message" => "Sign in success",
                "data" => [
                    [
                        "emp_id" =>$result[0]->emp_id,
                        "role"   =>$result[0]->role,
                        "name_th"   =>$result[0]->name_th,
                        "name_en"   =>$result[0]->name_en,
                        "token"   =>$token,
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
}
