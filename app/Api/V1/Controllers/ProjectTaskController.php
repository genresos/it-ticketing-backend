<?php

namespace App\Api\V1\Controllers;
use JWTAuth;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use App\Modules\PaginationArr;
use App\Http\Controllers\ProjectController;
use Symfony\Component\HttpKernel\Exception\QtyProgressNeededMatchHttpException;
use Symfony\Component\HttpKernel\Exception\CheckInValidationHttpException;


class ProjectTaskController extends Controller
{
    //
    use Helpers;

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
        $this->user_emp_no = Auth::guard()->user()->emp_no;
        $this->user_person_id = Auth::guard()->user()->person_id;
    }

    public function my_project_task(Request $request)
    {
        $myArray = ProjectController::project_task(
            $this->user_id,
            $this->user_emp_no,
            $this->user_person_id
        );
        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );

    }

    public function detail_reports($id)
    {
        $myArray = ProjectController::project_task_detail($id, $this->user_id);

        return $myArray;
    }

    public function check_in(Request $request, $id)
    {
        $myArray = ProjectController::project_task_check_in(
            $id,
            $this->user_id,
            $request->latitude,
            $request->longitude,
            $request->file('photo')
        );
    }

//==================================================================== CHECK OUT Task =============================================================\\

    public function check_out(Request $request, $id){  //$id = cico_id

                
        $currentUser = JWTAuth::parseToken()->authenticate();
        $person_id=Auth::guard()->user()->person_id;
        $emp_no=Auth::guard()->user()->emp_no;
        $session=Auth::guard()->user()->id;

        $fileupload=$request->all();

        if ($checkout=$request->file('photo')){
            $filename = "CO".$session.date('Ymd').rand(1,9999999999);
            $destination = public_path("/storage/project_task/images");
            $checkout->move($destination, $filename.".jpg"); 

            DB::table('0_project_task_cico')->where('id',$id)
                        ->update(array('check_out' => 1,
                        'lat_out' => $request->latitude,
                        'long_out' => $request->longitude,
                        'image_out' => $filename,
                        'end_time' => Carbon::now()));

        }
        
        
        return response()->json([
            'success' => true
        ],200);
    }


//==================================================================== UPDATE Task =============================================================\\

    public function update_task(Request $request, $id){  //$id = cico_id

                    
        $currentUser = JWTAuth::parseToken()->authenticate();
        $person_id=Auth::guard()->user()->person_id;
        $emp_no=Auth::guard()->user()->emp_no;
        $session=Auth::guard()->user()->id;

        $fileupload=$request->all();

        $sql = "SELECT * FROM 0_project_task_cico WHERE id = $id";
        $validation_cico = DB::select( DB::raw($sql));
        foreach($validation_cico as $data){
            if($data->check_out == 1){
                $aa = "You've been check out!";
                return response()->json([
                    'status' => false,
                    'data' => $aa
                ],403);
            }else if($data->check_out < 1){
                
                $progress_qty_ongoing = DB::table('0_project_task_progress')
                    ->where('id_cico',$id)
                    ->sum('qty');

                $qty_task_needed = DB::table('0_project_task')
                    ->where('id',$data->project_task_id)
                    ->sum('qty');

                $validation_qty_progress = $progress_qty_ongoing + $request->qty;

                if($validation_qty_progress <= $qty_task_needed)
                {

                        DB::table('0_project_task_progress')->insert(array('id_cico' => $id,
                                    'project_task_id' => $data->project_task_id,
                                    'date' => date('Y-m-d'),
                                    'description' => $request->description,
                                    'qty' => $request->qty,
                                    'updated_by' => $session));

                        if ($update1=$request->file('photo1')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update1->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update2=$request->file('photo2')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update2->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update3=$request->file('photo3')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update3->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update4=$request->file('photo4')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update4->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update5=$request->file('photo5')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update5->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update6=$request->file('photo6')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update6->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update7=$request->file('photo7')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update7->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update8=$request->file('photo8')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update8->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }

                        if ($update9=$request->file('photo9')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update9->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }
                        
                        if ($update10=$request->file('photo10')){
                            $filename = "PROGRESS".$session.date('Ymd').rand(1,9999999999);
                            $destination = public_path("/storage/project_task/images");
                            $update10->move($destination, $filename.".jpg"); 

                                    $sql1 = "SELECT * FROM 0_project_task_progress WHERE updated_by = $session ORDER BY date DESC LIMIT 1";
                                    $get_progress_id = DB::select( DB::raw($sql1));

                                    foreach($get_progress_id as $key){
                                        DB::table('0_project_progress_photos')->insert(array('project_task_id' => $key->project_task_id,
                                                'progress_id' => $key->id,
                                                'file_path' => $filename,
                                                'created_by' => $session));
                                    }

                        }


                        return response()->json([
                            'status' => true
                        ],200);
                        
                }else if($validation_qty_progress > $qty_task_needed){
                    throw new QtyProgressNeededMatchHttpException();
                }
               
                
            }
        }        
    
    }
}
