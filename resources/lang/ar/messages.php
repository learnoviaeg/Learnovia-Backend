<?php

// resources/lang/ar/messages.php

return [
    'error' => [
        'not_found' => 'لم يتم العثور على العنصر',
        'parent_cannot_submit' => 'لا يمكن للوالدين تقديم الإجابات',
        'user_not_assign' => 'لم يتم تعيين هذا المستخدم لهذا العنصر',
        'cannot_edit' => 'لا تستطيع أن تعدل على إجابتك',
        'submit_limit' => 'عذرًا ، لا يُسمح لك بتقديم إجابة بعد الآن',
        'quiz_time' => 'عذرًا ، الاختبار لم يبدأ بعد',
        'quiz_ended' => 'عذرًا ، الاختبار انتهى وقته',
        'not_available_now' => 'عذرًا ، هذا العنصر غير متوفر لك في الوقت الحالي',
        'try_again' => 'حدث خطأ ما. أعد المحاولة من فضلك',
        'data_invalid' => 'هذه البيانات غير صحيحة',
        'item_added_before' => 'تمت إضافة هذا العنصر من قبل',
        'cannot_delete' => 'لا يمكن حذف هذا العنصر',
        'item_deleted' => 'تمت إزالة هذا العنصر',
        'incomplete_data' => 'هذه البيانات غير كاملة',
        'grade_less_than' => 'من فضلك , ادخل درجة اقل من ',
        'no_available_data' => 'لا توجد بيانات متاحة للعرض',
        'cannot_see_feedback' => 'لا يسمح لك أن ترى ردود الفعل',
        'no_active_for_lesson' => 'لا يوجد تيرم مفعل لهذا الدرس',
        'already_exist' => 'العنصر موجود بالفعل',
        'no_active_segment' => 'لا يوجد تيرم مفعل لهذا العنصر',
        'no_active_year' => 'لا توجد سنة مفعلة متاحة',
        'extension_not_supported' => 'هذا الامتداد غير معتمد ',
        'no_permission' => 'المستخدم ليس لديه الإذن',
        'role_required' => 'من فضلك ، اختر الوظيفة أولا',
        'not_allowed' => 'غير مسموح بذلك',
        'not_allowed_to_edit' => 'لا يمكن التعديل',
        'not_allowed_to_add' => 'لا يمكن اضافة هذا العنصر',
        'extension_error' => 'هذا الامتداد غير صالح',
        'assigned_before' => 'تم اصافته من قبل'
    ],
    'success' => [
        'submit_success' => 'تم إرسال الإجابة بنجاح',
        'toggle' => 'تم تبديل العنصر بنجاح',
        'link_to_file' => 'رابط الملف',
        'data_imported' => 'تم رفع البيانات من الملف بنجاح',
        'user_list_items' => 'قائمة عناصر المستخدم',
    ],
    'lesson' => [
        'add' => 'تم اضافة الدرس بنجاح',
        'update' => 'تم تعديل الدرس بنجاح',
        'delete' => 'تم حذف الدرس بنجاح',
        'sort' => 'تم ترتيب الدروس بنجاح',
        'list' => 'قائمة الدروس',
    ],
    'assignment' => [
        'add' => 'تم اضافة الواجب بنجاح',
        'update' => 'تم تعديل الواجب بنجاح',
        'delete' => 'تم حذف الواجب بنجاح',
        'list' => 'قائمة الواجبات',
        'count' => 'عدد الواجبات',
        'assignment_not_belong' => 'هذا الواجب لا ينتمي إلى هذا الدرس',
        'assignment_object' => 'تفاصيل الواجب',
        'content_only' => 'من فضلك أدخل المحتوى فقط',
        'file_only' => 'من فضلك أدخل الملف فقط',
        'content_or_file' => 'من فضلك ، أدخل المحتوى أو الملف',
        'override' => 'تم تحديث تاريخ الواجب للطلاب المختارين بنجاح',
        'cant_update' => 'عذرًا ، أجاب الطلاب على هذه المهمة ، ولا يمكن تحديثها',
        'assignment_hidden'=> 'هذا الواجب قد تم إخفاءه'
    ],
    'grade' => [
        'graded' => 'تم إرسال الدرجة بنجاح',
        'update' => 'تم تعديل الدرجة بنجاح',
        'grading_method_list' => 'قائمة طرق الدرجات',
        'grade_category_list'  => 'قائمة اقسام الدرجات',
    ],
    'date' => [
        'end_before' => 'من فضلك أدخل تاريخ الانتهاء قبل '
    ],
    'question' => [
        'questions_answers_count' => 'عدد الأسئلة أكبر من عدد الإجابات',
        'add' => 'تم اضافة السؤال بنجاح',
        'update' => 'تم تعديل السؤال بنجاح',
        'delete' => 'تم حذف السؤال بنجاح',
        'count' => 'عدد الأسئلة',
        'answer_delete' => 'تم حذف إجابة السؤال بنجاح',
        'list' => 'قائمة الاسألة',
        'question_type_error' => 'هذا السؤال ليس مقال أو صحيح وخطأ',
    ],
    'answer' => [
        'add' => 'تم اضافة الإجابة بنجاح',
        'delete' =>'تم حذف الإجابة بنجاح',
        'not_belong_to_question' => 'هذه الإجابة لا تنتمي إلى هذا السؤال',
    ],
    'question_category' => [
        'add' => 'تمت إضافة قسم الأسئلة بنجاح',
        'update' => 'تم تعديل قسم الأسئلة بنجاح',
        'delete' => 'تم حذف قسم الأسئلة بنجاح',
        'list' => 'قائمة اقسام الأسئلة',
        'category_cannot_deleted' => 'هذا القسم لديه أسئلة ذات صلة ، لا يمكن حذفه',
        // 'cannot_deleted' => 'you can\'t delete this category',
    ],
    'quiz' => [
        'add' => 'تم اضافة الاختبار بنجاح',
        'update' => 'تم تعديل الاختبار بنجاح',
        'NotUpdate' => 'لا تستطيع التعديل على هذا الامتحان',
        'delete' => 'تم حذف الاختبار بنجاح',
        'assign' => 'تم اضافة الأسئلة للاختبار بنجاح',
        'list' => 'قائمة الاختبارات',
        'count' => 'عدد الاختبارات',
        'invaled_feedback' => 'نوع الملاحظات غير صحيح ، لا يمكن أن تكون الملاحظات بعد الإرسال',
        'quiz_not_belong' => 'هذا الاختبار لا ينتمي إلى هذا الدرس',
        'no_attempts' => 'هذا الاختبار لا يوجد لديه أي محاولات من i`h الطالب',
        'override' => 'تم تحديث تاريخ الاختبار للطلاب المختارين بنجاح',
        'continue_quiz' => 'يمكنك متابعة محاولتك الأخيرة',
        'students_attempts_list' => 'قائمة محاولات الطلاب',
        'quiz_object' => 'تفاصيل الاختبار',
        'quiz_hidden' => 'هذا الاختبار قد تم إخفاءه',
        'quiz_not_started' => 'هذا الاختبار لم يبدأ بعد',
        'grade_pass_settings' => 'تم اضافه اعدادات درجه النجاح',
        'grade_pass_settings_list' => ' اعدادات درجه النجاح',
        'wrong_date' => 'من فضلك أعد مراجعة التواريخ ووقت الامتحان',
        'quiz_notify' => 'لديك اختبار جديد :quizName في مادة :courseName'
    ],
    'permissions' => [
        'no_roles_assigned' => 'لا توجد وظائف معينة لهذا الإذن',
        'user_doesnot_has_permission' => 'ليس لديك هذه الصلاحية',
    ],
    'users' => [
        'students_list' => 'قائمة الطلاب',
        'parents_list' => 'قائمة الآباء',
        'teachers_list' => 'قائمة المعلمين',
        'parent_choose_child'=> 'من فضلك ، اختر طفلك أولا',
        'add' => 'تم إضافة المستخدم بنجاح',
        'update' => 'تم تعديل المستخدم بنجاح',
        'delete' => 'تم حذف المستخدم بنجاح',
        'cannot_delete' => 'لا تستطيع حذف هذا المستخدم',
        'list' => 'قائمة المستخدمين',
        'all_list' => 'قائمة جميع المستخدمين',
        'count' => 'عدد وظائف المستخدمين',
        'exeed_max_users' => 'عذرا ، لقد وصلت إلى الحد الأقصى. لا يمكنك إضافة المزيد من المستخدمين',
        'username_already_used' => 'اسم المستخدم مستخدم بالفعل',
        'user_blocked' => 'تم حظر المستخدمين بنجاح',
        'user_un_blocked' => 'تم إلغاء حظر المستخدمين بنجاح',
        'parent_assign_child' => 'تم تعيين الوالد للابن بنجاح',
        'parent_unassign_child' => 'تم ازالة تعيين الطفل للوالد بنجاح',
        'current_child' => 'طفلك الحالي هو ...',
        'childs_list' => 'قائمة الأطفال',
        'your_username_pass' => 'اسم المستخدم وكلمة المرور الخاصين بك',
    ],
    'page' => [
        'add' => 'تم اضافة الصفحة بنجاح',
        'update' => 'تم تعديل الصفحة بنجاح',
        'delete' => 'تم حذف الصفحة بنجاح',
        'list' => 'قائمة الصفحات',
        'page_not_belong' => 'هذه الصفحة لا تنتمي إلى هذا الدرس',
        'page_hidden' =>'هذه الصفحه قد تم اخفاءها'
    ],
    'topic' => [
        'add' => 'تم اضافة الموضوع بنجاح',
        'update' => 'تم تعديل الموضوع بنجاح',
        'delete' => 'تم حذف الموضوع بنجاح',
        'list' => 'قائمة الموضوعات',
     
    ],
    'file' => [
        'add' => 'تم تحميل الملف بنجاح',
        'update' => 'تم تعديل الملف بنجاح',
        'delete' => 'تم حذف الملف بنجاح',
        'list' => 'قائمة الملفات',
        'file_not_belong' => 'هذا الملف لا ينتمي إلى هذا الدرس',
        'file_hidden' => 'هذا الملف قد تم اخفاءه'
    ],
    'media' => [
        'add' => 'تم تحميل الوسائط بنجاح',
        'update' => 'تم تعديل الوسائط بنجاح',
        'delete' => 'تم حذف الوسائط بنجاح',
        'list' => 'قائمة الوسائط',
        'media_not_belong' => 'هذه الوسائط لا تنتمي إلى هذا الدرس',
        'only_url_or_media' => 'من فضلك ، إما تحميل الوسائط أو إضافة رابط',
        'media_hidden' => 'هذه الوسائط قد تم إخفاءها',

        
        'image' => [
            'add' => 'تم تحميل الصورة بنجاح',
            'update' => 'تم تعديل الصورة بنجاح',
            'delete' => 'تم حذف الصورة بنجاح',
        ],
        'video' => [
            'add' => 'تم تحميل الفيديو بنجاح',
            'update' => 'تم تعديل الفيديو بنجاح',
            'delete' => 'تم حذف الفيديو بنجاح',
        ],
        'audio' => [
            'add' => 'تم تحميل المقطع الصوتي بنجاح',
            'update' => 'تم تعديل المقطع الصوتي بنجاح',
            'delete' => 'تم حذف المقطع الصوتي بنجاح',
        ],
        'link' => [
            'add' => 'تم تحميل الرابط بنجاح',
            'update' => 'تم تعديل الرابط بنجاح',
            'delete' => 'تم حذف الرابط بنجاح',
        ],
        'url' => [
            'add' => 'تم تحميل الرابط بنجاح',
            'update' => 'تم تعديل الرابط بنجاح',
            'delete' => 'تم حذف الرابط بنجاح',
        ],
    ],

    'materials' => [
        'list' => 'قائمة الوسائط المتعددة',
        'count' => 'عدد الوسائط المتعددة',
    ],

    'attendance_session' => [
        'add' => 'تمت إضافة الحصة بنجاح',
        'update' => 'تم تعديل الحصة بنجاح',
        'delete' => 'تم حذف الحصة بنجاح',
        'delete_all' => 'تم حذف جميع الحصص بنجاح',
        'list' => 'قائمة الحصص',
        'same_time_session' => 'عذرا ، لا يمكنك إضافة حصص مختلفة لنفس الفصل.',
        'taken' => 'تم تسجيل الحضور بنجاح',
        'invalid_end_date' => ' تاريخ الانتهاء يجب ان يكون بين',
        'invalid_start_date' => ' تاريخ البدء يجب ان يكون بين',
    ],

    'session_reports' => [
        'daily' => 'تقرير الغياب اليومي',
        'per_session' => 'نقرير الغياب الحصصي'
    ],

    'virtual' => [
        'add' => 'تم إضافة الفصل الافتراضي بنجاح',
        'update' => 'تم تعديل الفصل الافتراضي بنجاح',
        'delete' => 'تم حذف الفصل الافتراضي بنجاح',
        'list' => 'قائمة الفصول الافتراضية',
        'cannot_join' => 'آسف ، لا يمكنك الانضمام إلى هذا الفصل الافتراضي',
        'join' => 'جاري الانضمام إلى الفصل الافتراضي ...',
        'no_one_entered' => 'لم يدخل أحد هذا الفصل الافتراضي',
        'virtual_hidden' => 'هذا الفصل الافتراضي قد تم اخفاءه',
        'server_error' => 'الفصول الافتراضية غير متوفرة في الوقت الحالي ',
        'current' => 'جاري',
        'past' => 'سابق',
        'future' => 'مقبل',

        'record' => [
            'list' => 'قائمة سجلات الفصول الافتراضية',
            'no_records' => 'لا توجد سجلات',
        ],

        'attendnace' => [
            'list' => 'قائمة حضور الفصول الافتراضية',
        ],
    ],
    'interactive' => [
        'add' => 'تم إضافة المحتوى التفاعلي بنجاح',
        'update' => 'تم تعديل المحتوى التفاعلي بنجاح',
        'delete' => 'تم حذف المحتوى التفاعلي بنجاح',
        'list' => 'قائمة التفاعلات',
        'count' => 'عدد التفاعلات',
        'interactive_not_belong' => 'هذا المحتوى التفاعلي لا ينتمي إلى هذا الدرس',
        'hidden' => "هذا المحتوي التفاعلي قد تم اخفاءه",
    ],
    'auth' => [
        'invalid_username_password' => 'خطأ في اسم المستخدم أو كلمة مرور',
        'blocked' => 'تم حظر حسابك',
        'login' => 'تم تسجيل الدخول بنجاح',
        'logout' => 'تم تسجيل الخروج بنجاح',
    ],
    'year' => [
        'add' => 'تم اضافة السنة الدراسية بنجاح',
        'update' => 'تم تعديل السنة الدراسية بنجاح',
        'delete' => 'تم حذف السنة الدراسية بنجاح',
        'list' => 'قائمة السنوات الدراسية',
    ],
    'type' => [
        'add' => 'تم اضافة النوع بنجاح',
        'update' => 'تم تعديل النوع بنجاح',
        'delete' => 'تم حذف النوع بنجاح',
        'list' => 'قائمة الانواع',
    ],
    'level' => [
        'add' => 'تم اضافة المستوى بنجاح',
        'update' => 'تم تعديل المستوى بنجاح',
        'delete' => 'تم حذف المستوى بنجاح',
        'list' => 'قائمة المستوىات',
    ],
    'class' => [
        'add' => 'تم اضافة الفصل بنجاح',
        'update' => 'تم تعديل الفصل بنجاح',
        'delete' => 'تم حذف الفصل بنجاح',
        'list' => 'قائمة الفصول',
    ],
    'segment' => [
        'add' => 'تم اضافة التيرم بنجاح',
        'update' => 'تم تعديل التيرم بنجاح',
        'delete' => 'تم حذف التيرم بنجاح',
        'list' => 'قائمة التيرمات',
        'type_invalid' => 'النوع المختار بلغ الحد الأقصى ، يرجى اختيار نوع آخر',
        'activate' => 'تم تفعيل التيرم',
    ],
    'course' => [
        'add' => 'تم اضافة المادة بنجاح',
        'update' => 'تم تعديل المادة بنجاح',
        'canNot' => 'لا يمكن تعديل المادة',
        'delete' => 'تم حذف المادة بنجاح',
        'list' => 'قائمة المواد',
        'object' => 'تفاصيل المادة',
        'assign' => 'تم تعيين المادة بنجاح',
        'template' => 'تم اضافة النموذج بنجاح',
        'anotherTemplate'=> 'هناك نموذج اخر في نفس المستوى برجاء تغييرة اولا',
    ],
    'announcement' => [
        'add' => 'تم إرسال الإعلان بنجاح',
        'update' => 'تم تعديل الإعلان بنجاح',
        'delete' => 'تم حذف الإعلان بنجاح',
        'list' => 'قائمة الإعلانات',
        'created_list'=> 'قائمة إعلاناتي',
        'object' => 'تفاصيل الإعلان',
    ],
    'role' => [
        'add' => 'تم اضافة الوظيفة بنجاح',
        'update' => 'تم تعديل الوظيفة بنجاح',
        'delete' => 'تم حذف الوظيفة بنجاح',
        'list' => 'قائمة الوظائف',
        'assign' => 'تم تعيين الوظيفة بنجاح',
        'revoke' => 'تمت إزالة الدور بنجاح',
    ],
    'enroll' => [
        'add' => 'تم تسجيل المستخدمين بنجاح',
        'delete' => 'تم إلغاء تسجيل المستخدم / المستخدمين بنجاح',
        'already_enrolled' => 'هؤلاء المستخدمون مسجلون بالفعل',
        'no_courses_belong_to_class' => 'لا توجد مواد تنتمي إلى هذا الفصل',
        'error' => 'اختار ترم صحيح'
    ],
    'zoom' => [
        'zoom_account' => 'ليس لدى المستخدم حساب على زوم',
        'Invalid' => 'الكود غير فعال',
    ],
    // 'status' => [
    //     // 'submitted' => 'تم التسليم',
    //     // 'not_submitted' => 'لم يتم التسليم',
    //     // 'graded' => 'مصحح',
    //     // 'not_graded' => 'غير مصحح',
    //     // 'no_answers' => 'لا توجد إجابات',
    // ],
    'grading_schema' => [
        'add'    => 'تم اضافة بنجاح',
        'list' => 'القائمة',
        'scales_assigned'=> 'تم إضافة المقياسات بنجاح',
        'delete' => 'تم الحذف بنجاح',
        'canNot_delete' => 'لا تستطيع الحذف'
    ],
    'grade_category' => [
        'add' => 'تم اضافة بنجاح',
        'update' => 'تم تعديل بنجاح',
        'list' => 'القائمة',
        'delete' => 'تم الحذف ',
        'CannotUpdate' => 'عذرًا ،لا يمكن التحديث',
        'category_cannot_deleted' => 'لايمكن الحذف ',
        'Done' => 'تم',
        'reArrange' => 'لا يمكن ترتيبه',
    ],
    // 'grading' => [
    //     'First' => 'First',
    //     'Last' => 'Last',
    //     'Average' => 'Average',
    //     'Highest' => 'Highest',
    //     'Lowest' => 'Lowest',
    // ],
    'grade_item' => [
        'add' => 'تم اضافة بنجاح',
        'update' => 'تم تعديل بنجاح',
        'list' => 'القائمة',
        'delete' => 'تم الحذف ',
    ],

    'user_grade' => [
        'update' => 'تم تعديل الدرجه بنجاح',
    ],
    'letter' => [
        'add' => 'تم اضافة بنجاح',
        'update' => 'تم تعديل بنجاح',
        'list' => 'القائمة',
        'delete' => 'تم الحذف ',
    ],
    'scale' => [
        'add' => 'تم اضافة بنجاح',
        'update' => 'تم تعديل بنجاح',
        'list' => 'القائمة',
        'delete' => 'تم الحذف ',
        'course' => 'مقاييس الماده',
        'cannot_update' => 'يمكن تعديل الاسم فقط',
        'cannot_delete' => 'لا يمكن الحذف',
    ],
    'working_day' => [
        'list' => 'الأيام',
        'update' => 'تم تحديث البيانات'
    ]
];