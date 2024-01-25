<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database
use Illuminate\Support\Facades\Cache;
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
            $decoded = $jwt->decoded;

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

            //decode role from token
            $roleToken = $decoded->role;
            $role_id = DB::table('tb_role as t1')->selectRaw('*')->leftJoin('tb_role_function as t2','t2.role_id','=','t1.role_id')->get();

            foreach ($role_id as $doc) {
                $role = [
                    'role_id' => $doc->role_id,
                    'role_desc' => $doc->role_desc,
                    'data' => [
                        'created_at' => $doc->created_at,
                        'updated_at' => $doc->updated_at,
                        'role_function_id' => $doc->role_function_id,
                        'function_desc' => $doc->function_desc,
                        'is_available' => $doc->is_available,
                    ],
                ];
            
                // Debugging: Print information for each iteration
                echo "Role: {$doc->role_desc}, Function: {$doc->function_desc}, isAvailable: {$doc->is_available}\n";
            
                // Check for "Create sub categories" function availability for any role
                if ($roleToken == $doc->role_desc && $doc->function_desc == 'Create sub categories' && $doc->is_available == true) {
                    
                    $categoryCheck = DB::table('tb_sub_service_categories')->select('*')->where('sub_category_desc',$request->sub_category_desc)->get();

                    if(count($categoryCheck) !=0){
                        return response()->json([
                            "status" => "error",
                            "message" => "the sub category has been in system",
                            "data" =>[]

                        ]);
                    }
            
                    // Insert data into the database
                    DB::table('tb_sub_service_categories')->insert([
                        "main_category_id" => $request->main_category_id,
                        'sub_category_desc' => $request->sub_category_desc
                    ]);

                    return response()->json([
                        "status"    => "success",
                        "message"   => 'Insert data successfully',
                        "data"      => [$request->sub_category_desc],
                    ], 200);
                }
            }
            // If the loop completes without finding a match, return an error
            return response()->json([
                "status" => "error",
                "message" => "Cannot access, you don't have permission.",
                "data" => [],
            ]);

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
            // API Caching
            $cacheKey = "/sub-oneway/get-all-cache";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData))  return response([
                "status" => "success",
                "message"=> "Data from chaced",
                "data" => json_decode($cacheData)
            ]);

            $get = DB::table('tb_sub_service_categories')->select('*')->get();

            Cache::put($cacheKey, \json_encode($get, JSON_UNESCAPED_UNICODE), \DateInterval::createFromDateString('1 minutes'));

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
            $decoded = $jwt->decoded;
            //decode role from token
            $roleToken = $decoded->role;

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

            $role_id = DB::table('tb_role as t1')->selectRaw('*')->leftJoin('tb_role_function as t2','t2.role_id','=','t1.role_id')->get();

            foreach ($role_id as $doc) {
                $role = [
                    'role_id' => $doc->role_id,
                    'role_desc' => $doc->role_desc,
                    'data' => [
                        'created_at' => $doc->created_at,
                        'updated_at' => $doc->updated_at,
                        'role_function_id' => $doc->role_function_id,
                        'function_desc' => $doc->function_desc,
                        'is_available' => $doc->is_available,
                    ],
                ];
                // Debugging: Print information for each iteration
                echo "Role: {$doc->role_desc}, Function: {$doc->function_desc}, isAvailable: {$doc->is_available}\n";

                // Check for "Update sub categories" function availability for any role
                if ($roleToken == $doc->role_desc && $doc->function_desc == 'Update sub categories' && $doc->is_available == true) {
                    $categoryCheck = DB::table('tb_sub_service_categories')->select('*')->where('sub_category_desc',$request->sub_category_desc)->get();

                    if(count($categoryCheck) !=0){
                        return response()->json([
                            "status" => "error",
                            "message" => "the sub category has been in system",
                            "data" =>[]
                        ]);
                    }

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
            }}
            // If the loop completes without finding a match, return an error
            return response()->json([
                "status" => "error",
                "message" => "Cannot access, you don't have permission.",
                "data" => [],
            ]);

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
            $decoded = $jwt->decoded;
            //decode role from token
            $roleToken = $decoded->role;

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

            $role_id = DB::table('tb_role as t1')->selectRaw('*')->leftJoin('tb_role_function as t2','t2.role_id','=','t1.role_id')->get();

            foreach ($role_id as $doc) {
                $role = [
                    'role_id' => $doc->role_id,
                    'role_desc' => $doc->role_desc,
                    'data' => [
                        'created_at' => $doc->created_at,
                        'updated_at' => $doc->updated_at,
                        'role_function_id' => $doc->role_function_id,
                        'function_desc' => $doc->function_desc,
                        'is_available' => $doc->is_available,
                    ],
                ];

                // Debugging: Print information for each iteration
                echo "Role: {$doc->role_desc}, Function: {$doc->function_desc}, isAvailable: {$doc->is_available}\n";

                // Check for "Delete sub categories" function availability for any role
                if ($roleToken == $doc->role_desc && $doc->function_desc == 'Delete sub categories' && $doc->is_available == true) {

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
                }}}

            // If the loop completes without finding a match, return an error
            return response()->json([
                "status" => "error",
                "message" => "Cannot access, you don't have permission.",
                "data" => [],
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
