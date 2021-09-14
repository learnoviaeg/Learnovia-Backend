<?php

return [
    'view' => [
        'api/timeline',
        'api/quizzes',
        'api/materials',
        'api/courses',
        'api/assignments',
        'api/quizzes',
        'api/quiz/get-single-quiz',
        'api/assignment/get',
        'api/questions',
        'api/question/category/get',
        'api/interactive',
        'api/bigbluebutton/get',
        'api/bigbluebutton/join',
        'api/bigbluebutton/general_report',
        'api/bigbluebutton/get-attendance',
        'api/announcement',
        'api/announcements/created',
        'api/attendance/get',
        'api/course/past',
        'api/course/ongoing',
        'api/course/future',
        'api/year/get-all',
        'api/type/get-all',
        'api/level/get-all',
        'api/class/get-all',
        'api/segment/get-all',
        'api/user/active',
        'api/user/in_active',
        'api/spatie/list-role-with-permissions',
        'api/user/get-all',
        'api/user/getParents',
        'api/page/get',
        'api/materials/{id}',
        'api/quiz/get-all-attempts',
        'api/user/set-current-child',
        'api/user/get-my-children',
        'api/interactive/{id}'
    ],

    'seen_report' => [
        'api/materials/{id}',
        'api/quiz/get-single-quiz',
        'api/assignment/get',
        'api/page/get',
        'api/interactive/{id}',
        'api/announcement/{announcement}'
    ],
];