<?php

use Illuminate\Database\Seeder;
use App\AcademicType;
use App\AcademicYear;
use App\AcademicYearType;
use App\Message;
use App\Parents;
use App\Message_Role;
use App\Announcement;
use App\attachment;
use App\Segment;
use App\Lesson;
use App\Component;
use App\Contacts;
use App\Level;
use App\Classes;
use App\Category;
use App\YearLevel;
use App\SegmentClass;
use App\ClassLevel;
use App\Course;
use App\Enroll;
use App\CourseSegment;
use App\User;

class SeedLearnoviaDB extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $AcademicTypes = [
            ['name' => 'National', 'segment_no' => 2],
            ['name' => ' British', 'segment_no' => 3],
            ['name' => 'American', 'segment_no' => 4],
        ];
        foreach ($AcademicTypes as $AcademicType) {
            AcademicType::create($AcademicType);
        }
        $AcademicYears = [
            ['name' => '2015','current' =>'1'],
            ['name' => '2016','current' =>'0'],
            ['name' => '2017','current' =>'0']
        ];
        foreach ($AcademicYears as $AcademicYear) {
            AcademicYear::create($AcademicYear);
        }
        $AcademicYearType = [
            ['academic_year_id' => 1, 'academic_type_id' => 2],
            ['academic_year_id' => 2, 'academic_type_id' => 2],
            ['academic_year_id' => 1, 'academic_type_id' => 3],
            ['academic_year_id' => 3, 'academic_type_id' => 3],
        ];
        foreach ($AcademicYearType as $AcademicYearTyp) {
            AcademicYearType::create($AcademicYearTyp);
        }
        $levels = [
            ['name' => 'primary one'],
            ['name' => 'primary two'],
            ['name' => 'primary three']
        ];
        foreach ($levels as $level) {
            Level::create($level);
        }
        $segments = [
            ['name' => 'term one', 'academic_type_id' => 2, 'current' => 1],
            ['name' => 'term two', 'academic_type_id' => 2],
            ['name' => 'term three', 'academic_type_id' => 2],
            ['name' => 'term one', 'academic_type_id' => 1, 'current' => 1],
            ['name' => 'term two', 'academic_type_id' => 1],
        ];
        foreach ($segments as $segment) {
            Segment::create($segment);
        }
        $classes = [
            ['name' => '1/1'],
            ['name' => '1/2'],
            ['name' => '1/3'],
            ['name' => '2/2'],
            ['name' => '2/1'],
            ['name' => '2/3'],
            ['name' => '4/4'],
        ];
        foreach ($classes as $class) {
            Classes::create($class);
        }
        $Categoty = [
            ['name' => 'Primary'],
            ['name' => 'Secndary'],
            ['name' => 'lesson three']
        ];
        foreach ($Categoty as $Categot) {
            Category::create($Categot);
        }
        $Courses = [
            ['name' => 'English', 'category_id' => 2],
            ['name' => ' Arabic', 'category_id' => 1],
            ['name' => 'French', 'category_id' => 3],
            ['name' => 'Math', 'category_id' => 3],
            ['name' => 'science', 'category_id' => 3],
        ];
        foreach ($Courses as $Course) {
            Course::create($Course);
        }
        $YearLevel = [
            ['level_id' => 1, 'academic_year_type_id' => 1],
            ['level_id' => 1, 'academic_year_type_id' => 2],
            ['level_id' => 2, 'academic_year_type_id' => 2],
            ['level_id' => 3, 'academic_year_type_id' => 2],

        ];
        foreach ($YearLevel as $YearLeve) {
            YearLevel::create($YearLeve);
        }
        $ClassLevels = [
            ['year_level_id' => 1, 'class_id' => 1],
            ['year_level_id' => 1, 'class_id' => 2],
            ['year_level_id' => 3, 'class_id' => 2],
            ['year_level_id' => 1, 'class_id' => 3],

        ];
        foreach ($ClassLevels as $ClassLevel) {
            ClassLevel::create($ClassLevel);
        }
        $SegmentClass = [
            ['class_level_id' => 1, 'segment_id' => 1],
            ['class_level_id' => 1, 'segment_id' => 2],
            ['class_level_id' => 1, 'segment_id' => 3],
            ['class_level_id' => 2, 'segment_id' => 1],
        ];
        foreach ($SegmentClass as $SegmentClas) {
            SegmentClass::create($SegmentClas);
        }
        $CourseSegment = [
            ['course_id' => 1, 'segment_class_id' => 1, 'start_date' => '2019-07-28 13:23:27', 'end_date' => '2019-07-28 13:23:27'],
            ['course_id' => 2, 'segment_class_id' => 1, 'is_active' => 1],
            ['course_id' => 2, 'segment_class_id' => 2, 'start_date' => '2019-07-28 13:23:27', 'end_date' => '2019-07-28 13:23:27'],
            ['course_id' => 2, 'segment_class_id' => 3, 'is_active' => 1],
            ['course_id' => 3, 'segment_class_id' => 3, 'is_active' => 1],
            ['course_id' => 1, 'segment_class_id' => 2, 'start_date' => '2019-07-28 13:23:27', 'end_date' => '2019-07-28 13:23:27'],

        ];
        foreach ($CourseSegment as $CourseSegmen) {
            CourseSegment::create($CourseSegmen);
        }
        $annoncement = [
            ['title' => 'Announcement one ', 'description' => 'this is description of  Announcement one ',
                'class_id' => 1, 'level_id' => 2, 'course_id' => 2],
            ['title' => 'Announcement two ', 'description' => 'this is description of  Announcement two ',
                'class_id' => 2, 'level_id' => 1, 'course_id' => 3],
            ['title' => 'Announcement 3 ', 'description' => 'this is description of  Announcement 3 ',
                'class_id' => 2, 'level_id' => 1, 'course_id' => 3],
        ];

        foreach ($annoncement as $annoncemen) {
            Announcement::create($annoncemen);
        }
        $users = [

            ['firstname' => 'Hend', 'email' => 'Hend-v@gmail.com', 'password' => bcrypt('123456'), 'real_password' => '123456', 'lastname' => 'kagha', 'username' => 'Learn0002'],
            ['firstname' => 'Mariam', 'email' => 'Mariam@gmail.com', 'password' => bcrypt('123456'), 'real_password' => '123456', 'lastname' => 'samir', 'username' => 'Learn0003'],
            ['firstname' => 'Salma', 'email' => 'Salma@gmail.com', 'password' => bcrypt('123456'), 'real_password' => '123456', 'lastname' => 'Amr', 'username' => 'Learn0004'],
            ['firstname' => 'Miirna', 'email' => 'Miirna@gmail.com', 'password' => bcrypt('123456'), 'real_password' => '123456', 'lastname' => 'Mourad', 'username' => 'Learn0005'],
            ['firstname' => 'Huda', 'email' => 'Huda@gmail.com', 'password' => bcrypt('123456'), 'real_password' => '123456', 'lastname' => 'Mahmoud', 'username' => 'Learn0006'],
        ];
        foreach ($users as $user) {
            User::create($user);
        }
        $attachments = [
            ['name' => 'file', 'path' => 'C:/DeskTop', 'description' => 'this is description of this file', 'type' => 'Quiz', 'extension' => '.pdf'],
            ['name' => 'file', 'path' => 'C:/DeskTop', 'description' => 'this is description of this file', 'type' => 'Assignment', 'extension' => '.jpg'],
            ['name' => 'file', 'path' => 'C:/DeskTop', 'description' => 'this is description of this file', 'type' => 'Quiz', 'extension' => '.png']
        ];
        foreach ($attachments as $attachment) {
            attachment::create($attachment);
        }
        $components = [
            ['name' => 'Quiz', 'module' => 'QuestionBank', 'model' => 'Quiz', 'type' => '1'],
            ['name' => 'Media', 'module' => 'UploadFiles', 'model' => 'Media', 'type' => '1'],
            ['name' => 'File', 'module' => 'UploadFiles', 'model' => 'File', 'type' => '1'],
            ['name' => 'Assignment', 'module' => 'Assigments', 'model' => 'Assignment', 'type' => '1'],
        ];
        foreach ($components as $component) {
            Component::create($component);
        }
        $Contacts = [
            ['Person_id' => 1, 'Friend_id' => 2],
            ['Person_id' => 1, 'Friend_id' => 3],
            ['Person_id' => 1, 'Friend_id' => 4],
            ['Person_id' => 1, 'Friend_id' => 5],
            ['Person_id' => 2, 'Friend_id' => 5],
            ['Person_id' => 2, 'Friend_id' => 4],
            ['Person_id' => 2, 'Friend_id' => 3],
        ];
        foreach ($Contacts as $Contact) {
            Contacts::create($Contact);
        }
        $Lessons = [
            ['name' => 'lesson one', 'course_segment_id' => 1, 'index' => 1],
            ['name' => 'lesson two', 'course_segment_id' => 1, 'index' => 2],
            ['name' => 'lesson one', 'course_segment_id' => 2, 'index' => 1],
        ];
        foreach ($Lessons as $Lesson) {
            Lesson::create($Lesson);
        }
        $messages = [
            ['text' => 'Hello', 'From' => 1, 'about' => 1, 'seen' => 0, 'deleted' => 0, 'To' => 2],
            ['text' => 'Hello', 'From' => 1, 'about' => 1, 'seen' => 0, 'deleted' => 0, 'To' => 3],
            ['text' => 'Hello', 'From' => 1, 'about' => 1, 'seen' => 0, 'deleted' => 0, 'To' => 4],
            ['text' => 'Hello', 'From' => 1, 'about' => 1, 'seen' => 0, 'deleted' => 0, 'To' => 5],
            ['text' => 'Hello', 'From' => 3, 'about' => 3, 'seen' => 0, 'deleted' => 0, 'To' => 2],
        ];
        foreach ($messages as $message) {
            Message::create($message);
        }
        $message_Roles = [
            ['From_Role' => '1', 'To_Role' => 1],
            ['From_Role' => '1', 'To_Role' => 2],
            ['From_Role' => '1', 'To_Role' => 3],
            ['From_Role' => '1', 'To_Role' => 4],
            ['From_Role' => '2', 'To_Role' => 5],
            ['From_Role' => '3', 'To_Role' => 5],
            ['From_Role' => '4', 'To_Role' => 5],
            ['From_Role' => '5', 'To_Role' => 5],

        ];
        foreach ($message_Roles as $message_Role) {
            Message_Role::create($message_Role);
        }

        $Enrolls = [
            ['user_id' => 2,'course_segment' => 1, 'role_id' => 4],
            ['user_id' => 3,'course_segment' => 1, 'role_id' => 3],
            ['user_id' => 4,'course_segment' => 2, 'role_id' => 4],
            ['user_id' => 5,'course_segment' => 1, 'role_id' => 3],
            ['user_id' => 6,'course_segment' => 2, 'role_id' => 3],
            ['user_id' => 6,'course_segment' => 3, 'role_id' => 3],
            ['user_id' => 6, 'course_segment' => 1, 'role_id' => 3],

        ];
        foreach ($Enrolls as $user) {
            Enroll::create($user);
        }
        $parents = [
            ['parent_id' => 2, 'child_id' => 5],
            ['parent_id' => 2, 'child_id' => 3],
            ['parent_id' => 2, 'child_id' => 4],

        ];
        foreach ($parents as $parent) {
            Parents::create($parent);
        }
    }
}
