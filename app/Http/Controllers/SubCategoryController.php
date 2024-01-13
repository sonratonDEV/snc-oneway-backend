<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

use DateTime;

class SubCategoryController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

//* [POST] sub-oneway/create
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
                "sub_category_desc"    => ["required", "string"],
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

            DB::table('tb_sub_service_categories')->insert([
                "main_category_id" => $request->main_category_id,
                'sub_category_desc' => $request->sub_category_desc
            ]);

            return response()->json([
                "status"    => "success",
                "message"   => 'Insert data successfully',
                "data"      => [$request->sub_category_desc],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [GET] /sub-oneway/get-all
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

            $get = DB::table('tb_sub_service_categories')->select('*')->get();

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

//* [UPDATE] /sub-oneway/update
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
                "sub_category_id"      => ["required", "uuid"],
                "main_category_id"      => ["required", "uuid"],
                "sub_category_desc"    => ["required", "string", "min:1"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_sub_service_categories")->where(
                "sub_category_id", $request->sub_category_id)->where(
                "main_category_id", $request->main_category_id
            )->update([
                "sub_category_desc"    => $request->sub_category_desc,
                "updated_at"            => DB::raw("now()"),
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated sub category success",
                "data" => [$request->sub_category_desc],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

//* [DELETE] /sub-oneway/delete
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
                "sub_category_id"      => ["required", "uuid"],
                "main_category_id"      => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            // DB::table("tb_sub_service_categories")->where("sub_category_id", $request->sub_category_id)->where("main_category_id", $request->main_category_id)->delete();
            $count_result= DB::table("tb_services")->where("sub_category_id", $request->sub_category_id)->where("main_category_id", $request->main_category_id)->count();
            
            if(($count_result) !=0 ){
                return response()->json([
                    "status" => "success",
                    "message" => "Can not deleted main category",
                    "data" => [
                            $count_result
                    ],
                ], 201);

            } else{
                DB::table("tb_sub_service_categories")->where("sub_category_id", $request->sub_category_id)->where("main_category_id", $request->main_category_id)->delete();

                return response()->json([
                "status" => "success",
                "message" => "Deleted sub category success",
                "data" => [],
            ], 201);
            }

            // return response()->json([
            //     "status" => "success",
            //     "message" => "Deleted main category success",
            //     "data" => [],
            // ], 201);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
}
