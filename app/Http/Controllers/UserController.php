<?php
/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;
use App\User;
use App\Course;
use App\CourseSegment;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\EnrollUserToCourseController;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\ClassLevel;
use App\SegmentClass;

class UserController extends Controller
{
    /*
        @Description:: This Function is for creating new user.
        @Param:: name, email [must be correct and unique], password[must be more than or equal 8].
        @Output:: 'if every thing correct' -> User Created Successfully.
                  'else' -> Error.
    */
    public function create(Request $request)
    {
        $request->validate([
            'firstname' => 'required|array',
            'firstname.*' => 'required|string|min:3|max:50',
            'lastname' => 'required|array',
            'lastname.*' => 'required|string|min:3|max:50',
            'password' => 'required|array',
            'password.*' => 'required|string|min:8|max:191',
            'role' => 'required|array',
            'optional.*' => 'exists:courses,name',
            'optional' => 'array',
            'course.*' => 'exists:courses,name',
            'course' => 'array',
            'role.*' => 'required|exists:roles,id',
            'class_id' => 'required|array',
        ]);
        $users = collect([]);
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email'];
        $enrollOptional = 'optional';
        $teacheroptional='course';
        foreach ($request->firstname as $key => $firstname) {
            $user = User::create([
                'firstname' => $firstname,
                'lastname' => $request->lastname[$key],
                'username' => User::generateUsername(),
                'password' => bcrypt($request->password[$key]),
                'real_password'=> $request->password[$key]
            ]);

            foreach ($optionals as $optional) {
                if ($request->filled($optional))
                    $user->$optional = $request->$optional;
            }
            $user->save();
            $role = Role::find($request->role[$key]);
            $user->assignRole($role);
            if ($request->role[$key] == 3) {

                $classLevID=ClassLevel::GetClass($request->class_id[$key]);
                $classSegID=SegmentClass::GetClasseLevel($classLevID);

                $option = new Request([
                    'username' => $user->username,
                    'start_date' => $request->start_date[$key],
                    'end_date' => $request->end_date[$key],
                    'SegmentClassId' => $classSegID
                ]);
                EnrollUserToCourseController::EnrollInAllMandatoryCourses($option);
                $enrollcounter=0;
                while(isset($request->$enrollOptional[$key][$enrollcounter])) {
                    $course_id=Course::findByName($request->$enrollOptional[$key][$enrollcounter]);
                    $segmentid= CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'start_date' => $request->start_date[$key],
                        'users'=> array($user->username),
                        'end_date' => $request->end_date[$key],
                        'role_id'=>array(3)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $enrollcounter++;
                }
            }
            else{
                $teachercounter=0;

                while(isset($request->$teacheroptional[$key][$teachercounter])){

                    $course_id=Course::findByName($request->$teacheroptional[$key][$teachercounter]);
                    $segmentid= CourseSegment::getidfromcourse($course_id);
                    $option = new Request([
                        'course_segment' => array($segmentid),
                        'start_date' => $request->start_date[$key],
                        'users'=> array($user->username),
                        'end_date' => $request->end_date[$key],
                        'role_id'=>array($role->id)
                    ]);
                    EnrollUserToCourseController::EnrollCourses($option);
                    $teachercounter++;
                }
            }
            $users->push($user);
        }
        return HelperController::api_response_format(201, $users, 'User Created Successfully');

    }

    /*
        @Description:: This Function is for Update user.
        @Param::id, name, email [must be correct and unique], password[must be more than or equal 8].
        @Output:: 'if every thing correct' -> User Updated Successfully.
                  'else' -> Error.
    */

    public function update(Request $request)
    {
        $optionals = ['arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'email'];
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);

        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => ['required',Rule::unique('users')->ignore($user->id),'email'],
            'password' => 'required|string|min:8|max:191'
        ]);
        $check = $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        foreach ($optionals as $optional) {
            if ($request->filled($optional))
                $user->$optional = $request->$optional;
        }
        $user->save();
        return HelperController::api_response_format(201, $user, 'User Updated Successfully');

    }

    /*
       @Description:: This Function is for Delete user.
       @Param:: id.
       @Output:: 'if User Exist' -> User Deleted Successfully.
                 'else' -> Error.
   */

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);
        $user->delete();

        return HelperController::api_response_format(201, null, 'User Deleted Successfully');

    }

    /*
       @Description:: This Function is for List All users.
       @Output:: All users in system.
   */

    public function list()
    {
        $user = User::all(['id', 'name', 'email', 'suspend', 'created_at']);
        return HelperController::api_response_format(201, $user);
    }

    /*
       @Description:: This Function is for Block a user.
       @Param::id.
       @Output:: 'if user found' -> User Blocked Successfully.
                 'else' -> Error.
   */

    public function suspend_user(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);
        $check = $user->update([
            'suspend' => 1
        ]);
        return HelperController::api_response_format(201, $user, 'User Blocked Successfully');

    }

    /*
       @Description:: This Function is for un Block a user.
       @Param::id.
       @Output:: 'if user found' -> User un Blocked Successfully.
                'else' -> Error.
   */

    public function unsuspend_user(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);
        $user = User::find($request->id);
        $check = $user->update([
            'suspend' => 0
        ]);
        return HelperController::api_response_format(201, $user, 'User Un Blocked Successfully');

    }

}
