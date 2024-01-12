<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; //ตัวเช็คข้อมูล
use Illuminate\Support\Facades\DB; //import database

use App\Http\Libraries\JWT\JWTUtils; //JWT

use DateTime;

class EventController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

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

            $rules = [
                "event_category_id" => ["required", "uuid"],
                "event_name"        => ["required", "string"],
                "event_desc"        => ["required", "string"],
                "image"             => ["required", "string"],
                "video_url"         => ["required", "string"],
                "ref_url"           => ["required", "string"],
                "started_at"        => ["required", "date"],
                "finished_at"       => ["required", "date"],
                // "creator_id"        => ["required", "string"]

            ];

            $validator = Validator::make($request -> all(), $rules);
            if ($validator->fails()){
                return response() -> json([
                    'status' => 'error',
                    'message' => 'Bad request',
                    'data' => [
                        ['validator' => $validator->errors()]
                    ]
                ],400);
            }

            DB::table('tb_events')->insert([
                "event_category_id" => $request->event_category_id,
                'event_name' => $request->event_name,
                "event_desc" => $request->event_desc,
                "image" => $request->image,
                "video_url" => $request->video_url,
                "ref_url" => $request->ref_url,
                "started_at" => $request->started_at,
                "finished_at"=> $request->finished_at,
                "creator_id" => $decoded->emp_id,
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

    // function getAll(Request $request){
    //     try {
    //         $header = $request -> header("Authorization");
    //         $jwt = $this -> jwtUtils->verifyToken($header);
    //         if($jwt->state == false){
    //             return response() -> json([
    //                 "status" => 'error',
    //                 "message" => "Unauthorized, please login",
    //                 "data" => [],
    //             ]);
    //         }

    //         $get = DB::table('tb_services')->select('*')->get();

    //         return response()->json([
    //             "status"    => "success",
    //             "message"   => 'Select data successfully',
    //             "data"      => [$get],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status"    => "error",
    //             "message"   => $e->getMessage(),
    //             "data"      => [],
    //         ], 500);
    //     }
    // }

    // function update(Request $request)
    // {
    //     try {
    //         $authorize = $request->header("Authorization");
    //         $jwt = $this->jwtUtils->verifyToken($authorize);
    //         if (!$jwt->state) return response()->json([
    //             "status" => "error",
    //             "message" => "Unauthorized",
    //             "data" => []
    //         ], 401);

    //         $rules = [
    //             "service_id"       => ["required", "uuid"],
    //             "sub_category_id"      => ["required", "uuid"],
    //             "main_category_id"      => ["required", "uuid"],
    //             "service_name"    => ["required", "array"],
    //         ];

    //         $validator = Validator::make($request->all(), $rules);
    //         if ($validator->fails()) return response()->json([
    //             "status" => "error",
    //             "message" => "Bad request",
    //             "data" => [
    //                 ["validator" => $validator->errors()]
    //             ]
    //         ], 400);

    //         DB::table("tb_sub_service_categories")
    //         ->where("service_id", $request->service_id)
    //         ->where("sub_category_id", $request->sub_category_id)
    //         ->where("main_category_id", $request->main_category_id)
    //         ->update([
    //             "service_name"    => ($request->sub_category_desc),
    //             "updated_at"      => DB::raw("now()"),
    //         ]);

    //         return response()->json([
    //             "status" => "success",
    //             "message" => "Updated main category success",
    //             "data" => [($request->sub_category_desc)],
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status"    => "error",
    //             "message"   => $e->getMessage(),
    //             "data"      => [],
    //         ], 500);
    //     }
    // }
    
    // function delete(Request $request)
    // {
    //     try {
    //         $authorize = $request->header("Authorization");
    //         $jwt = $this->jwtUtils->verifyToken($authorize);
    //         if (!$jwt->state) return response()->json([
    //             "status" => "error",
    //             "message" => "Unauthorized",
    //             "data" => []
    //         ], 401);

    //         $rules = [
    //             "sub_category_id"   => ["required", "uuid"],
    //             "main_category_id"  => ["required", "uuid"],
    //             "service_id"       => ["required", "uuid"]
    //         ];

    //         $validator = Validator::make($request->all(), $rules);
    //         if ($validator->fails()) return response()->json([
    //             "status" => "error",
    //             "message" => "Bad request",
    //             "data" => [
    //                 ["validator" => $validator->errors()]
    //             ]
    //         ], 400);

    //         DB::table("tb_services")->where("sub_category_id", $request->sub_category_id)
    //         ->where("main_category_id", $request->main_category_id)->where("service_id", $request->seervice_id)->delete();

    //         return response()->json([
    //             "status" => "success",
    //             "message" => "Deleted main category success",
    //             "data" => [],
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status"    => "error",
    //             "message"   => $e->getMessage(),
    //             "data"      => [],
    //         ], 500);
    //     }
    // }

//* [GET] /event/pending-approvals
    function pendingApprovals(Request $request){
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

            $result = DB::table('tb_events as t1')->selectRaw(
                "t1.event_id, 
                t1.event_name, 
                t1.event_desc, 
                t1.creator_id, 
                t1.image,
                t1.video_url,
                t1.ref_url,
                t2.name ->>'th' as creator_name,
                t1.created_at :: varchar(19) as created_at, 
                t1.updated_at :: varchar(19) as updated_at")
            ->leftjoin(
                "tb_employees as t2",
                "t1.creator_id",
                "=",
                "t2.emp_id")
            ->where("t1.is_approved", null)->whereBetween(DB::raw("now()"), [DB::raw("t1.started_at"),  DB::raw("t1.finished_at")])
            ->orderBy("created_at")->get();

            // $result = DB::select("select 
            // t1.event_id
            // ,t1.event_name
            // ,t1.event_desc
            // ,t1.creator_id
            // ,t2.name->>'th' as name_th
            // ,t1.created_at::varchar(19) as created_at
            // ,t1.updated_at::varchar(19) as updated_at
            // from tb_events as t1
            // left join tb_employees as t2
            // on t1.creator_id=t2.emp_id
            // where t1.is_approved is null and now() between t1.started_at and t1.finished_at;");

            return response()->json([
                "status"    => "success",
                "message"   => 'Select data successfully',
                "data"      => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

    //* [GET] /event/events?limit_event=<limit_event>&page_number=<page_number>
    function events(Request $request){
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);
 
            $rules = [
                "limit_event" => ["required", "integer", "min:1",],
                "page_number" => ["required", "integer", "min:1"],
            ];
 
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);
 
            $result = DB::select(
                "select
                _event_id as event_id
                ,_event_name as event_name
                ,_event_desc as event_desc
                ,_image as image
                ,_video_url as video_url
                ,_ref_url as ref_url
                ,_creator_id as creator_id
                ,_creator_name as creator_name
                ,_started_at as started_at
                ,_finished_at as finished_at
                ,_created_at as created_at
                ,_updated_at as updated_at
                from fn_find_events(?, ?);",
                [$request->limit_event, $request->page_number]
            );
 
            return response()->json([
                "status" => "success",
                "message" => "Data from query",
                "data" => $result,
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