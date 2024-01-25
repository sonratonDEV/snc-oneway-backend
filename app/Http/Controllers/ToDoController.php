<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

use DateTime;

class ToDoController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }
//* [POST] /eventcat-oneway/create
    function create(Request $request){
        try {
            $authorize = $request -> header("Authorization");
            $jwt = $this -> jwtUtils->verifyToken($authorize);
            if($jwt->state == false){
                return response() -> json([
                    "status" => 'error',
                    "message" => "Unauthorized, please login",
                    "data" => [],
                ], 401);
            }
            $decoded = $jwt->decoded;

            $result = [

                "todo_desc" => ["required", "string"],

            ];

            $validator = Validator::make($request -> all(), $result);
            if ($validator->fails()){
                return response() -> json([
                    'status' => 'error',
                    'message' => 'Bad request',
                    'data' => [
                        ['validator' => $validator->errors()]
                    ]
                ],400);
            }

            DB::table('tb_todo')->insert([
                "todo_desc" => $request->todo_desc,
                "creator_id" => $decoded->emp_id,
            ]);

            return response()->json([
                "status"    => "success",
                "message"   => 'Insert data successfully',
                "data"      => [$request->todo_desc],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [GET] /eventcat-oneway/get-all
    function getAll(Request $request){
        try {
            $authorize = $request -> header("Authorization");
            $jwt = $this -> jwtUtils->verifyToken($authorize);
            if($jwt->state == false){
                return response() -> json([
                    "status" => 'error',
                    "message" => "Unauthorized, please login",
                    "data" => [],
                ], 401);
            }
            $decoded = $jwt->decoded;

            $get = DB::table('tb_todo')->select('*')->where('creator_id',$decoded)->get();

            return response()->json([
                "status"    => "success",
                "message"   => 'Select data successfully',
                "data"      => [$get],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [UPDATE] /eventcat-oneway/update
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

            $result = [
                "todo_id"      => ["required", "uuid"],
                "todo_desc"     => ["required", "string"],
            ];

            $validator = Validator::make($request->all(), $result);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_todo")->where("todo_id", $request->todo_id)
            ->update([
                "todo_desc"    => ($request->todo_desc),
                "updated_at"      => DB::raw("now()"),
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated todo success",
                "data" => [$request->todo_desc],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [DELETE] /eventcat-oneway/delete
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

            $result = [
                "todo_id"       => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $result);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_todo")->where("todo_id", $request->todo_id)->delete();

            return response()->json([
                "status" => "success",
                "message" => "Deleted todo success",
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
}
