<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

use DateTime;

class ServiceCategoryController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }
//* [POST] /service-oneway/create
    function create(Request $request){
        try {
            $header = $request -> header("Authorization");
            $jwt = $this -> jwtUtils->verifyToken($header);
            if($jwt->state == false){
                return response() -> json([
                    "status" => 'error',
                    "message" => "Unauthorized, please login",
                    "data" => [],
                ], 401);
            }

            $rules = [
                "main_category_id"      => ["required", "uuid"],
                "sub_category_id"    => ["required", "uuid"],
                "service_name"    => ["required", "array"],
            ];

            $validator = Validator::make(
                $request -> all(), $rules);
            if ($validator->fails()){
                return response() -> json([
                    'status' => 'error',
                    'message' => 'Bad request',
                    'data' => [
                        ['validator' => $validator->errors()]
                    ]
                ],400);
            }

            DB::table('tb_services')->insert([
                "main_category_id" => $request->main_category_id,
                'sub_category_id' => $request->sub_category_id,
                "service_name" => json_encode($request->service_name)
            ]);

            return response()->json([
                "status"    => "success",
                "message"   => 'Insert data successfully',
                "data"      => [json_encode($request->service_name)],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
//* [GET] /service-oneway/get-all
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

            $get = DB::table('tb_services')->select('*')->get();

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

//* [UPDATE] /service-oneway/update
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
                "service_id"       => ["required", "uuid"],
                "sub_category_id"      => ["required", "uuid"],
                "main_category_id"      => ["required", "uuid"],
                "service_name"    => ["required", "array"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_services")
            ->where("service_id", $request->service_id)
            ->where("sub_category_id", $request->sub_category_id)
            ->where("main_category_id", $request->main_category_id)
            ->update([
                "service_name"    => json_encode($request->service_name), 
                "updated_at"      => DB::raw("now()"),
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated main category success",
                "data" => [json_encode($request->service_name)], 
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
    
//* [DELETE] /service-oneway/delete
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
                "sub_category_id"   => ["required", "uuid"],
                "main_category_id"  => ["required", "uuid"],
                "service_id"       => ["required", "uuid"]
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_services")->where("sub_category_id", $request->sub_category_id)
            ->where("main_category_id", $request->main_category_id)->where("service_id", $request->seervice_id)->delete();

            return response()->json([
                "status" => "success",
                "message" => "Deleted main category success",
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
