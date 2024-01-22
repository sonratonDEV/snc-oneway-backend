<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT
class RoleAccessController extends Controller
{
     private $jwtUtils;
     public function __construct()
     {
         $this->jwtUtils = new JWTUtils();
     }
 
 //* [POST] /access-oneway/create
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
                     'service_id' => ["required", "uuid"],
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
             $service_id = $request -> service_id;
 
             DB::table('tb_role_access')->insert([
                 'role_id' => $role_id,
                 'service_id'=> $service_id
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
 
             $get = DB::table('tb_role_access as t1')->selectRaw(
             "t1.role_access_id,
             t1.is_available,
             t2.role_desc,
             t3.service_name",
             )->Join('tb_role as t2','t2.role_id','=','t1.role_id'
             )->leftJoin('tb_services as t3','t3.service_id','=','t1.service_id'
             )->where("t1.is_available", true
             )->get();

             foreach ($get as $doc){
                $doc->service_name = json_decode($doc->service_name);
            }
 
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
 
 //* [UPDATE] /access-oneway/update
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
                'role_access_id' => ["required", "uuid"],
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
 
             DB::table("tb_role_access")->where("role_access_id", $request->role_access_id)->update([
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
 
 //* [DELETE] /access-oneway/delete
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
                'role_access_id' => ["required", "uuid"],
             ];
 
             $validator = Validator::make($request->all(), $rules);
             if ($validator->fails()) return response()->json([
                 "status" => "error",
                 "message" => "Bad request",
                 "data" => [
                     ["validator" => $validator->errors()]
                 ]
             ], 400);
 
                 DB::table("tb_role_access")->where("role_access_id", $request->role_access_id)->delete();
 
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
