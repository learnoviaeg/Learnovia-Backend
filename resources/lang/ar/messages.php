<?php

// resources/lang/ar/messages.php

return [
    'error' => [
        'not_found' => 'لم يتم العثور على العنصر',
        'parent_cannot_submit' => 'لا يمكن للوالدين تقديم الإجابات',
        'user_not_assign' => 'لم يتم تعيين هذا المستخدم لهذا العنصر',
        'submit_limit' => 'عذرًا ، لا يُسمح لك بتقديم إجابة بعد الآن',
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
    ],
    'success' => [
        'submit_success' => 'تم إرسال الإجابة بنجاح',
        'toggle' => 'تم تبديل العنصر بنجاح',
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
        'no_active_for_lesson' => 'لا يوجد ترم مفعل لهذا الدرس',
        'assignment_not_belong' => 'هذا الواجب لا ينتمي إلى هذا الدرس',
        'assignment_object' => 'تفاصيل الواجب',
        'content_only' => 'من فضلك أدخل المحتوى فقط',
        'file_only' => 'من فضلك أدخل الملف فقط',
        'content_or_file' => 'من فضلك ، أدخل المحتوى أو الملف',
        'override' => 'تم تحديث تاريخ الواجب للطلاب المختارين بنجاح',
        'cant_update' => 'عذرًا ، أجاب الطلاب على هذه المهمة ، ولا يمكن تحديثها',
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
    ],
    'quiz' => [
        'add' => 'تم اضافة الاختبار بنجاح',
        'update' => 'تم تعديل الاختبار بنجاح',
        'delete' => 'تم حذف الاختبار بنجاح',
        'list' => 'قائمة الاختبارات',
        'invaled_feedback' => 'نوع الملاحظات غير صحيح ، لا يمكن أن تكون الملاحظات بعد الإرسال',
        'quiz_not_belong' => 'هذا الاختبار لا ينتمي إلى هذا الدرس',
        'override' => 'تم تحديث تاريخ الاختبار للطلاب المختارين بنجاح',
        'continue_quiz' => 'يمكنك متابعة محاولتك الأخيرة',
        'students_attempts_list' => 'قائمة محاولات الطلاب',
    ],
    'permissions' => [
        'no_roles_assigned' => 'لا توجد وظائف معينة لهذا الإذن'
    ],
    'users' => [
        'students_list' => 'قائمة الطلاب',
        'parent_choose_child'=> 'من فضلك ، اختر طفلك أولا',
    ],
];