<?php

namespace App\Http\Controllers\Admin;
use Aws\S3\S3Client;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title="employees";
        $designations = Designation::get();
        $departments = Department::get();
        $employees = Employee::with('department','designation')->get();
        return view('backend.employees',
        compact('title','designations','departments','employees'));
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
   public function list()
   {
       $title="employees";
       $designations = Designation::get();
       $departments = Department::get();
       $employees = Employee::with('department','designation')->get();
       return view('backend.employees-list',
       compact('title','designations','departments','employees'));
   }

/**
 * Store a newly created resource in storage.
 *
 * @param \Illuminate\Http\Request $request
 * @return \Illuminate\Http\Response
 */
public function store(Request $request)
{
  $this->validate($request, [
    'firstname' => 'required',
    'lastname' => 'required',
    'email' => 'required|email',
    'phone' => 'nullable|max:15',
    'company' => 'required|max:200',
    'avatar' => 'nullable|file|image|mimes:jpg,jpeg,png,gif',
    'department' => 'required',
    'designation' => 'required',
  ]);

  $imageName = null;
  $s3 = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-1', // Replace with your AWS region
    'credentials' => [
      'key' => 'AKIA3FLD5YAWN3WRKQEQ',
      'secret' => 'VCMqrIP2fmFEE6Uy9+bhRBN0EBdKhRW5yRgfknko',
    'bucket' => 'smarthremp'
    ],
  ]);

  if ($request->hasFile('avatar')) {
    $employeeName = $request->firstname . ' ' . $request->lastname;

    // Create folder path within the bucket using employee name
    $folderPath = $employeeName . '/';

    $fileName = time() . '.' . $request->avatar->extension();

    try {
      // Use the folder path in the 'Key' parameter
      $s3->putObject([
        'Bucket' => 'smarthremp',
        'Key' => $folderPath . $fileName,
        'Body' => fopen($request->avatar->getRealPath(), 'r'),
        'ACL' => 'private', // Adjust visibility as needed (public, private, etc.)
      ]);
      $imageName = $folderPath . $fileName; // Update with folder path
    } catch (Aws\Exception\AwsException $e) {
      // Handle S3 upload exception
      return back()->with('error', 'Failed to upload avatar: ' . $e->getMessage());
    }
  }

  $uuid = IdGenerator::generate(['table' => 'employees', 'field' => 'uuid', 'length' => 7, 'prefix' => 'EMP-']);
  Employee::create([
    'uuid' => $uuid,
    'firstname' => $request->firstname,
    'lastname' => $request->lastname,
    'email' => $request->email,
    'phone' => $request->phone,
    'company' => $request->company,
    'department_id' => $request->department,
    'designation_id' => $request->designation,
    'avatar' => $imageName, // Update with folder path
  ]);

  return back()->with('success', "Employee has been added");
}


        

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request,[
            'firstname'=>'required',
            'lastname'=>'required',
            'email'=>'required|email',
            'phone'=>'nullable|max:15',
            'company'=>'required|max:200',
            'avatar'=>'file|image|mimes:jpg,jpeg,png,gif',
            'department'=>'required',
            'designation'=>'required',
        ]);
        if ($request->hasFile('avatar')){
            $imageName = time().'.'.$request->avatar->extension();
            $request->avatar->move(public_path('storage/employees'), $imageName);
        }else{
            $imageName = Null;
        }
        
        $employee = Employee::find($request->id);
        $employee->update([
            'uuid' => $employee->uuid,
            'firstname'=>$request->firstname,
            'lastname'=>$request->lastname,
            'email'=>$request->email,
            'phone'=>$request->phone,
            'company'=>$request->company,
            'department_id'=>$request->department,
            'designation_id'=>$request->designation,
            'avatar'=>$imageName,
        ]);
        return back()->with('success',"Employee details has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $employee = Employee::find($request->id);
        $employee->delete();
        return back()->with('success',"Employee has been deleted");
    }
}
