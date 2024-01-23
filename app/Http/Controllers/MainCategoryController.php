<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

use DateTime;

class MainCategoryController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

//* [POST] /main-oneway/create
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
            $decoded = $jwt->decoded;

            $validator = Validator::make(
                $request -> all(),
                [
                    'main_category_desc' => 'required|string'
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

            //decode role from token
            $roleToken = $decoded->role;

            // return response() -> json([$roleToken]);

            $role_id = DB::table('tb_role as t1')->selectRaw('*')->leftJoin('tb_role_function as t2','t2.role_id','=','t1.role_id')->get();
           
            $formattedRoles = [];

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
            
                $formattedRoles[] = $role;
            
                // Debugging: Print information for each iteration
                echo "Role: {$doc->role_desc}, Function: {$doc->function_desc}, isAvailable: {$doc->is_available}\n";
            
                // Check for "Create main categories" function availability for any role
                if ($roleToken == $doc->role_desc && $doc->function_desc == 'Create main categories' && $doc->is_available == true) {
                    $mainCategoryDesc = $request->main_category_desc;
            
                    // Insert data into the database
                    DB::table('tb_main_service_categories')->insert([
                        'main_category_desc' => $mainCategoryDesc
                    ]);
            
                    return response()->json([
                        "status" => "success",
                        "message" => 'Insert data successfully',
                        "data" => [$mainCategoryDesc],
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


//* [GET] /main-oneway/get-all
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

            $get = DB::table('tb_main_service_categories')->select('*')->get();

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

//* [UPDATE] /main-oneway/update
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
                "main_category_id"      => ["required", "uuid"],
                "main_category_desc"    => ["required", "string", "min:1"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            DB::table("tb_main_service_categories")->where("main_category_id", $request->main_category_id)->update([
                "main_category_desc"    => $request->main_category_desc,
                "updated_at"            => DB::raw("now()"),
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated main category success",
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

//* [DELETE] /main-oneway/delete
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

            // DB::table("tb_main_service_categories")->where("main_category_id", $request->main_category_id)->delete();
            $count_result= DB::table("tb_sub_service_categories")->where("main_category_id", $request->main_category_id)->count();
            
            if(($count_result) !=0 ){
                return response()->json([
                    "status" => "success",
                    "message" => "Can not deleted main category",
                    "data" => [
                            $count_result
                    ],
                ], 201);

            } else{
                DB::table("tb_main_service_categories")->where("main_category_id", $request->main_category_id)->delete();

                return response()->json([
                "status" => "success",
                "message" => "Deleted main category success",
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
