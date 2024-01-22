<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

class RoleFunctionController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

//* [POST] /function-oneway/create
    function create(Request $request){
        try {
            $header = $request -> header("Authorization");
            $jwt = $this -> jwtUtils->verifyToken($header);
            if($jwt->state == false){
                return response() -> json([
                    "status" => 'error',
                    "message" => "Unauthorized, please login",
                    "data" => [],
                ]);
            }

            $validator = Validator::make(
                $request -> all(),
                [
                    'role_id' => ["required", "uuid"],
                    'function_desc' => ["required", "string", "min:1"],
                ]
            );
            if ($validator->fails()){
                return response() -> json([
                    'status' => 'error',
                    'message' => 'Bad request',
                    'data' => [
                        ['validator' => $validator->errors()]
                    ]
                ],400);
            }

            $role_id = $request -> role_id;
            $function_desc = $request -> function_desc;

            DB::table('tb_role_function')->insert([
                'role_id' => $role_id,
                'function_desc'=> $function_desc
            ]);

            return response()->json([
                "status"    => "success",
                "message"   => 'Insert data successfully',
                "data"      => [],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [GET] /access-oneway/get-all
    function getAll(Request $request){
        try {
            $header = $request -> header("Authorization");
            $jwt = $this -> jwtUtils->verifyToken($header);
            if($jwt->state == false){
                return response() -> json([
                    "status" => 'error',
                    "message" => "Unauthorized, please login",
                    "data" => [],
                ]);
            }

            $get = DB::table('tb_role_function')->select("*"
            )->get();

        //     foreach ($get as $doc){
        //        $doc->service_name = json_decode($doc->service_name);
        //    }

            return response()->json([
                "status"    => "success",
                "message"   => 'Select data successfully',
                "data"      => $get,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [UPDATE] /function-oneway/update
    function update(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);

            $rules = [
               'role_function_id' => ["required", "uuid"],
               'role_id' => ["required", "uuid"],
               'function_desc' => ["required", "string", "min:1"],
               "is_available"=> ["required","boolean"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_role_function")->where("role_function_id", $request->role_function_id)->update([
                "role_id"=> $request->role_id,
                "function_desc"=> $request->function_desc,
                "is_available"    => $request->is_available,
                "updated_at"            => DB::raw("now()"),
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated role access success",
                "data" => [],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [DELETE] /function-oneway/delete
    function delete(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);

            $rules = [
                'role_function_id' => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

                DB::table("tb_role_function")->where("role_access_id", $request->role_function_id)->delete();

                return response()->json([
                "status" => "success",
                "message" => "Deleted role access success",
                "data" => [],
            ], 201);
            }

       catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
}
