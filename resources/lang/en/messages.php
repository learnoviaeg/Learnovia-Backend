<?php

// resources/lang/en/messages.php

return [ 
    'error' => [
        'not_found' => 'Item is not found!',
        'parent_cannot_submit' => 'Parents can not submit answers',
        'user_not_assign' => 'This user is not assigned to this item',
        'cannot_edit' => 'you can\'t edit you submission',
        'submit_limit' => 'Sorry, you are not allowed to submit anymore',
        'quiz_time' => 'Sorry, Quiz isn\'t started yet',
        'quiz_ended' => 'Sorry, Quiz ended',
        'not_available_now' => 'Sorry, This item is not available for you at this moment',
        'try_again' => 'Something went wrong, please try again',
        'data_invalid' => 'This data is invalid',
        'item_added_before' => 'This item added before',
        'cannot_delete' => 'This item cannot be deleted',
        'item_deleted' => 'This item has been removed',
        'incomplete_data' => 'This data is incomplete',
        'grade_less_than' => 'Please, put grade less than ',
        'no_available_data' => 'No available data to show',
        'cannot_see_feedback' => 'You are not allowed to see feedback',
        'no_active_for_lesson' => 'No active segment for this lesson',
        'already_exist' => 'Item is already exist',
        'no_active_segment' => 'There is no active segment for this item',
        'no_active_year' => 'There is no active year available',
        'extension_not_supported' => 'extension is not valid',
        'no_permission' => 'User does not have the right permissions',
        'role_required' => 'Please, choose role first',
        'not_allowed' => 'You are not allowed to view this.',
        'not_allowed_to_edit' => 'You are not allowed to edit this.',
        'not_allowed_to_add' => 'You are not allowed to add this.',
        'not_allowed_to_delete' => 'You are not allowed to delete this.',
        'assigned_before' => 'Assigned before',
        'extension_error' => 'This extension is not valid'
    ],
    // 'grading' => [
    //     'First' => 'الأولى',
    //     'Last' => 'الأخيرة',
    //     'Average' => 'المنوسط',
    //     'Highest' => 'الأعلى',
    //     'Lowest' => 'الأقل',
    // ],
    'success' => [
        'submit_success' => 'Answer submitted successfully',
        'toggle' => 'Item toggle successfully',
        'link_to_file' => 'Link to the file',
        'data_imported' => 'Data imported successfully',
        'user_list_items' => 'User list of items',
    ],
    'lesson' => [
        'add' => 'Lesson added successfully',
        'update' => 'Lesson updated successfully',
        'delete' => 'Lesson deleted successfully',
        'sort' => 'Lessons sorted successfully',
        'list' => 'Lessons List',
    ],
    'assignment' => [
        'add' => 'Assignment added successfully',
        'update' => 'Assignment updated successfully',
        'delete' => 'Assignment deleted successfully',
        'list' => 'Assignments List',
        'count' => 'Assignments count',
        'assignment_not_belong' => 'This assignment does not belong to this lesson',
        'assignment_object' => 'Assignment details',
        'content_only' => 'Please, enter only content',
        'file_only' => 'Please, enter only file',
        'content_or_file' => 'Please, enter content or file',
        'override' => 'Assignment date updated to selected students successfully',
        'cant_update' => 'Sorry, students answered this assignment, cannot be updated',
        'assignment_hidden' => 'This assignment is hidden '
    ],
    'grade' => [
        'graded' => 'Grade submitted successfully',
        'update' => 'Grade edited successfully',
        'grading_method_list' => 'Grading methods list',
        'grade_category_list' => 'Grade categories list',
    ],
    'date' => [
        'end_before' => 'Please, enter due date before '
    ],
    'question' => [
        'questions_answers_count' => 'Questions number is greater than numbers of answers',
        'add' => 'Question added successfully',
        'update' => 'Question updated successfully',
        'delete' => 'Question deleted successfully',
        'count' => 'Questions count',
        'answer_delete' => 'Question answer deleted successfully',
        'list' => 'Questions List',
        'question_type_error' => 'This Question is not Essay or true and false',
    ],
    'answer' => [
        'add' => 'Answer added successfully',
        'delete' => 'Answer deleted successfully',
        'not_belong_to_question' => 'This answer is not belong to this question',
    ],
    'question_category' => [
        'add' => 'Questions category added successfully',
        'update' => 'Questions category updated successfully',
        'delete' => 'Questions category deleted successfully',
        'list' => 'Questions categories List',
        'category_cannot_deleted' => 'This category has related questions, cannot be deleted',
        'cannot_deleted' => 'you can\'t delete this category',
    ],
    'quiz' => [
        'add' => 'Quiz added successfully',
        'update' => 'Quiz updated successfully',
        'NotUpdate' => 'you can\'t update this quiz',
        'delete' => 'Quiz deleted successfully',
        'assign' => 'Questions assigned successfully',
        'unAssign' => 'Questions unAssigned successfully',
        'list' => 'Quizzes List',
        'count' => 'Quizzes count',
        'invaled_feedback' => 'Invalid feedback type, feedback can not be After submission',
        'quiz_not_belong' => 'This quiz does not belong to this lesson',
        'no_attempts' => 'This user_quiz does not have any atempts',
        'override' => 'Quiz date updated to selected students successfully',
        'continue_quiz' => 'You can continue your last attempt',
        'students_attempts_list' => 'Students attempts list',
        'quiz_object' => 'Quiz details',
        'quiz_hidden' => 'This quiz is hidden',
        'quiz_not_started' => 'This quiz didnt start yet',
        'grade_pass_settings' => 'Grade to pass settings added successfully',
        'grade_pass_settings_list' => 'Grade to pass settings',
        'wrong_date' => 'You can\'t update with this date please, Revision duration and end_date',
        'quiz_notify' => 'You have a new quiz :quizName in course :courseName'
    ],
    'permissions' => [
        'no_roles_assigned' => 'There is no roles assigned to this permission',
        'user_doesnot_has_permission' => 'You do not have the permission',
    ],
    'users' => [
        'students_list' => 'Students list',
        'parents_list' => 'Parents list',
        'teachers_list' => 'Teachers list',
        'parent_choose_child'=> 'Please, choose your child first',
        'add' => 'User added successfully',
        'update' => 'User updated successfully',
        'delete' => 'User/s deleted successfully',
        'cannot_delete' => 'You can\'t delete this user',
        'list' => 'Users list',
        'all_list' => 'All users list',
        'count' => 'Users roles count',
        'exeed_max_users' => 'Sorry, you have reached the maximum. you cannot add anymore users',
        'username_already_used' => 'Username is used already',
        'user_blocked' => 'Users blocked successfully',
        'user_un_blocked' => 'Users unblocked successfully',
        'parent_assign_child' => 'Parent assigned to child successfully',
        'parent_unassign_child' => 'Parent un assigned to child successfully',
        'current_child' => 'Your current child is...',
        'childs_list' => 'Children list',
        'your_username_pass' => 'Your username and password',
    ],
    'page' => [
        'add' => 'Page added successfully',
        'update' => 'Page updated successfully',
        'delete' => 'Page deleted successfully',
        'list' => 'Pages List',
        'page_not_belong' => 'This page does not belong to this lesson',
        'page_hidden' => 'This page is hidden'
    ],

    'topic' => [
        'add' => 'Topic added successfully',
        'update' => 'Topic updated successfully',
        'delete' => 'Topic deleted successfully',
        'list' => 'Topics List',
    ],
    'file' => [
        'add' => 'File uploaded successfully',
        'update' => 'File updated successfully',
        'delete' => 'File deleted successfully',
        'list' => 'Files List',
        'file_not_belong' => 'This file does not belong to this lesson',
        'file_hidden' => 'This file is hidden'
    ],
    'media' => [
        'add' => 'Media uploaded successfully',
        'update' => 'Media updated successfully',
        'delete' => 'Media deleted successfully',
        'list' => 'Medias List',
        'media_not_belong' => 'This media does not belong to this lesson',
        'only_url_or_media' => 'Please, either upload media or add a URL',
        'media_hidden' => 'This media is hidden',

        'image' => [
            'add' => 'Image uploaded successfully',
            'update' => 'Media updated successfully',
            'delete' => 'Image deleted successfully',
        ],
        'video' => [
            'add' => 'Video uploaded successfully',
            'update' => 'Media updated successfully',
            'delete' => 'Video deleted successfully',
        ],
        'audio' => [
            'add' => 'Audio uploaded successfully',
            'update' => 'Media updated successfully',
            'delete' => 'Audio deleted successfully',
        ],
        'link' => [
            'add' => 'Link uploaded successfully',
            'update' => 'Media updated successfully',
            'delete' => 'Link deleted successfully',
        ],
        'url' => [
            'add' => 'URL uploaded successfully',
            'update' => 'Media updated successfully',
            'delete' => 'URL deleted successfully',
        ],
    ],
    'materials' => [
        'list' => 'Materials list',
        'count' => 'Materials count',
    ],
    'attendance' => [
        'add' => 'Attendance added successfully',
        'update' => 'Attendance updated successfully',
        'delete' => 'Attendance deleted successfully',
        'list' => 'Attendances list',
    ],
    'attendance_session' => [
        'add' => 'Session added successfully',
        'update' => 'Session updated successfully',
        'delete' => 'Session deleted successfully',
        'delete_all' => 'All Sessions deleted successfully',
        'list' => 'Sessions list',
        'same_time_session' => 'Sorry you cannot add different sessions to the same class',
        'taken' => 'Attendance taken successfully',
        'invalid_end_date' => 'end date must be between ',
        'invalid_start_date' => 'start date must be between ',
    ],
    'session_reports' => [
        'daily' => 'Daily Report',
        'per_session' => 'Per Session Report'
    ],
    'virtual' => [
        'add' => 'Virtual classroom/s added successfully',
        'update' => 'Virtual classroom updated successfully',
        'delete' => 'Virtual classroom deleted successfully',
        'list' => 'Virtual classrooms list',
        'cannot_join' => 'Sorry, you cannot join this virtual classroom',
        'join' => 'Joining virtual classroom...',
        'no_one_entered' => 'No one entered this virtual classroom',
        'virtual_hidden' => "This virtual classroom is hidden",
        'server_error' => 'Virtual classrooms is not available for now',
        'current' => 'Current',
        'past' => 'Past',
        'future' => 'Future',

        'record' => [
            'list' => 'Virtual classrooms records list',
            'no_records' => 'No records found', 
        ],

        'attendnace' => [
            'list' => 'Virtual classroom attendnace list',
        ],
    ],
    'interactive' => [
        'add' => 'Interactive content added successfully',
        'update' => 'Interactive content updated successfully',
        'delete' => 'Interactive content deleted successfully',
        'list' => 'Interactive list',
        'count' => 'Interactive count',
        'interactive_not_belong' => 'This interactive content does not belong to this lesson',
        'hidden' => "This Interactive content is hidden",
    ],
    'auth' => [
        'invalid_username_password' => 'Invalid username or password',
        'blocked' => 'Your account is blocked',
        'login' => 'logged in successfully',
        'logout' => 'logged out successfully',
    ],
    'year' => [
        'add' => 'Academic year added successfully',
        'update' => 'Academic year updated successfully',
        'delete' => 'Academic year deleted successfully',
        'list' => 'Academic years List',
    ],
    'type' => [
        'add' => 'Type added successfully',
        'update' => 'Type updated successfully',
        'delete' => 'Type deleted successfully',
        'list' => 'Types List',
    ],
    'level' => [
        'add' => 'Level added successfully',
        'update' => 'Level updated successfully',
        'delete' => 'Level deleted successfully',
        'list' => 'Levels List',
    ],
    'class' => [
        'add' => 'Class added successfully',
        'update' => 'Class updated successfully',
        'delete' => 'Class deleted successfully',
        'list' => 'Classes List',
    ],
    'segment' => [
        'add' => 'Segment added successfully',
        'update' => 'Segment updated successfully',
        'delete' => 'Segment deleted successfully',
        'list' => 'Segments List',
        'type_invalid' => 'The chosen type has reached his maximum, please choose another',
        'activate' => 'Segment activated successfully',
    ],
    'course' => [
        'add' => 'Course added successfully',
        'update' => 'Course updated successfully',
        'canNot' => 'You can\'t update this course',
        'delete' => 'Course deleted successfully',
        'list' => 'Courses List',
        'object' => 'Course details',
        'assign' => 'Course assigned successfully',
        'template' => 'Templates added successfully',
        'anotherTemplate'=> 'There is another template in this level. Please change it first.',
    ],
    'announcement' => [
        'add' => 'Announcement sent successfully',
        'update' => 'Announcement updated successfully',
        'delete' => 'Announcement deleted successfully',
        'list' => 'Announcements List',
        'created_list' => 'My Announcements List',
        'object' => 'Announcement details',
    ],
    'role' => [
        'add' => 'Role added successfully',
        'update' => 'Role updated successfully',
        'delete' => 'Role deleted successfully',
        'list' => 'Roles List',
        'assign' => 'Role assigned successfully',
        'revoke' => 'Role revoked successfully',
    ],
    'enroll' => [
        'add' => 'Users enrolled successfully',
        'delete' => 'User/s unenrolled successfully',
        'already_enrolled' => 'Those users are already enrolled',
        'no_courses_belong_to_class' => 'No courses belong to this class',
        'error' => 'please, check segment'
    ],
    'zoom' => [
        'zoom_account' => 'user hasn\'t account on zoom',
        'Invalid' => 'Invalid Access Token',
    ],
    'status' => [
        'submitted' => 'Submitted',
        'not_submitted' => 'Not submitted',
        'graded' => 'Graded',
        'not_graded' => 'Not graded',
        'no_answers' => 'No answers',
    ],
    'grading_schema' => [
        'add'    => 'Grading schema added successfully',
        'list' => 'grading schema list',
        'scales_assigned'=> 'Scales assign successfully'
    ],
    'grade_category' => [
        'add'    => 'Grade category added successfully',
        'update' => 'Grade category is updated successfully',
        'list' => 'Grade Categories List',
        'delete' => 'Grade category deleted successfully',
        'not_found' => 'Can\'t find grade category',
        'CannotUpdate' => 'you can\'t update this category',
        'category_cannot_deleted' => 'Can\'t delete this category, please delete quiz',
        'reArrange' => 'you can\'t reArrange',
        'Done' => 'Done'
    ],
    'grade_item' => [
        'add'    => 'Grade item added successfully',
        'update' => 'Grade item is updated successfully',
        'list' => 'Grade items List',
        'delete' => 'Grade item deleted successfully',
    ],
    'logo' => [
        'set' => 'logo added successfully',
        'delete' => 'logo deleted successfully',
        'update' => 'Updated Successfully',
        'faild' => 'there is no logo',
        'get' => 'logo is'
    ],
    'user_grade' => [
        'update' => 'Grade is updated succefully',
    ],
    'letter' => [
        'add' => 'Letter added successfully',
        'delete' => 'Letter deleted successfully',
        'update' => 'Letter updated Successfully',
        'list' => 'Letter'
    ],
    'scale' => [
        'add' => 'Scale added successfully',
        'delete' => 'Scale deleted successfully',
        'update' => 'Scale updated Successfully',
        'list' => 'Scale',
        'course' => 'Scales of a course',
        'cannot_update' => 'Only name can be edited because the scale is assigned to grade category ',
        'cannot_delete' => 'Cannot be deleted because the scale is assigned to grade category',
    ],
    'working_day' => [
        'list' => 'All Days',
        'update' => 'Days updated successfully'
    ]
];