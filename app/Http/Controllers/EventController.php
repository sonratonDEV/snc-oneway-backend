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
    private function randomName(int $length = 5)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return \implode($pass); //turn the array into a string
    }


//* [POST] /event-oneway/create
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
            #create folder
            $path = getcwd() . "\\..\\images\\events\\";
            if (!is_dir($path)) mkdir($path, 0777, true);

            $fileName = $this->randomName(5) . time() . ".png";

            //* Write file
            file_put_contents($path . $fileName, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image)));

            //return response()->json(["path" =>is_dir($path)]);

            DB::table('tb_events')->insert([
                "event_category_id" => $request->event_category_id,
                'event_name' => $request->event_name,
                "event_desc" => $request->event_desc,
                "image" => $fileName,
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

   
//* [GET] /event/pending-approvals
    function pendingApprovals(Request $request){
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);

            $result = DB::table("tb_events as t1")->selectRaw(
                "t1.event_id
                ,t1.event_name
                ,t1.event_desc
                ,t1.creator_id
                ,t2.name->>'th' as creator_name
                ,t1.created_at::varchar(19) as created_at
                ,t1.updated_at::varchar(19) as updated_at"
            )->leftJoin(
                "tb_employees as t2",
                "t1.creator_id",
                "=",
                "t2.emp_id"
            )->where("t1.is_approved", null)->whereBetween(DB::raw("now()"), [DB::raw("t1.started_at"), DB::raw("t1.finished_at")])->orderBy("created_at")->get();

            // $result = DB::select("select 
            // t1.event_id
            // ,t1.event_name
            // ,t1.event_desc
            // ,t1.creator_id
            // ,t2.name->>'th' as creator_name
            // ,t1.created_at::varchar(19) as created_at
            // ,t1.updated_at::varchar(19) as updated_at
            // from tb_events as t1
            // left join tb_employees as t2
            // on t1.creator_id=t2.emp_id
            // where t1.is_approved is null and now() between t1.started_at and t1.finished_at;");

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

            foreach ($result as $row) {
                $row->image = is_null($row->image) ? null : "http://localhost/snc-oneway-tutorials/images/events/" . $row->image;
            }
 
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

    //* [GET] /event/event-count
    function eventCount(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);

            $result = DB::table("tb_events")->selectRaw(
                "count(event_id) as event_count"
            )->where("is_approved", true)->whereBetween(DB::raw("now()"), [DB::raw("started_at"), DB::raw("finished_at")])->get();

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

    //? [PUT] /event
    function update(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                // "message" => $jwt->msg,
                "data" => []
            ], 401);
            // $decoded = $jwt->decoded;

            $rules = [
                "event_id"          => ["required", "uuid"],
                "event_category_id" => ["required", "uuid"],
                "event_name"        => ["required", "string", "min:2"],
                "event_desc"        => ["required", "string"],
                "image"             => ["nullable", "string"],
                "video_url"         => ["nullable", "string"],
                "ref_url"           => ["nullable", "string"],
                "started_at"        => ["required", "date"],
                "finished_at"       => ["required", "date"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            //! Block by Start-End
            $result = DB::table("tb_events")->select(["event_id"])->where("event_id", $request->event_id)
                ->whereBetween(DB::raw("now()"), [DB::raw("started_at"), DB::raw("finished_at")])->get();
            if (count($result) === 0) return response()->json([
                "status" => "error",
                "message" => "This event is expired",
                "data" => [],
            ]);
            //! ./Block by Start-End

            $data = [
                "event_category_id" => $request->event_category_id,
                "event_name"        => $request->event_name,
                "event_desc"        => $request->event_desc,
                "video_url"         => $request->video_url,
                "ref_url"           => $request->ref_url,
                "started_at"        => $request->started_at,
                "finished_at"       => $request->finished_at,
                "updated_at"        => DB::raw("now()"),
            ];

            if (!is_null($request->image)) {
                //* Create Folder
                $path = getcwd() . "\\..\\..\\images\\events\\";
                if (!is_dir($path)) mkdir($path, 0777, true);

                //! Delete old file
                $checkFile = DB::table("tb_events")->select(["image"])->where("event_id", $request->event_id)->whereRaw("image is not null")->get();
                if (count($checkFile) !== 0) {
                    $oldFilePath = $path . $checkFile[0]->image;
                    if (file_exists($oldFilePath)) unlink($path . $checkFile[0]->image);
                }

                $newFileName = $this->randomName(5) . time() . ".png";
                //* Write file
                file_put_contents($path . $newFileName, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image)));

                $data["image"] = $newFileName;
            }

            $result = DB::table("tb_events")->where("event_id", $request->event_id)->update($data);

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "event_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated event successfully",
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
    //! [DELETE] /event
    function delete(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                // "message" => $jwt->msg,
                "data" => []
            ], 401);
            // $decoded = $jwt->decoded;

            $rules = [
                "event_id"          => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            $result = DB::table("tb_events")->where("event_id", $request->event_id)->delete();

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "event_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Deleted event successfully",
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

    //? [PATCH] /event/approve
    function approve(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                // "message" => $jwt->msg,
                "data" => []
            ], 401);
            // $decoded = $jwt->decoded;

            $rules = [
                "event_id"          => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            $result = DB::table("tb_events")->where("event_id", $request->event_id)->where("is_approved", null)->update(["is_approved" => true]);

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "event_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Approved event successfully",
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
    function disapprove(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                // "message" => $jwt->msg,
                "data" => []
            ], 401);
            // $decoded = $jwt->decoded;

            $rules = [
                "event_id"          => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            $result = DB::table("tb_events")->where("event_id", $request->event_id)->where("is_approved", null)->update(["is_approved" => false]);

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "event_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Disapproved event successfully",
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
