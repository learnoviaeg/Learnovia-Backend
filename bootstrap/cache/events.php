<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
    ),
    'App\\Events\\MassLogsEvent' => 
    array (
      0 => 'App\\Listeners\\MassLogsListener',
    ),
    'App\\Events\\GradeItemEvent' => 
    array (
      0 => 'App\\Listeners\\ItemDetailslistener',
    ),
    'App\\Events\\QuizAttemptEvent' => 
    array (
      0 => 'App\\Listeners\\AttemptItemlistener',
    ),
    'App\\Events\\RefreshGradeTreeEvent' => 
    array (
      0 => 'App\\Listeners\\RefreshGradeTreeListener',
    ),
    'App\\Events\\UpdatedAttemptEvent' => 
    array (
      0 => 'App\\Listeners\\FireAutoCorrectionEventListener',
    ),
    'App\\Events\\GradeAttemptEvent' => 
    array (
      0 => 'App\\Listeners\\GradeAttemptItemlistener',
    ),
    'App\\Events\\UpdatedQuizQuestionsEvent' => 
    array (
      0 => 'App\\Listeners\\UpdateQuizGradeListener',
      1 => 'App\\Listeners\\createTimelineListener',
      2 => 'App\\Listeners\\updateWeightDetailsListener',
    ),
    'App\\Events\\UserEnrolledEvent' => 
    array (
      0 => 'App\\Listeners\\AddUserGradersListener',
    ),
    'App\\Events\\CourseCreatedEvent' => 
    array (
      0 => 'App\\Listeners\\EnrollAdminListener',
    ),
    'App\\Events\\LessonCreatedEvent' => 
    array (
      0 => 'App\\Listeners\\AddSecondChainListener',
    ),
    'App\\Events\\ManualCorrectionEvent' => 
    array (
      0 => 'App\\Listeners\\GradeManualListener',
    ),
    'App\\Events\\updateQuizAndQuizLessonEvent' => 
    array (
      0 => 'App\\Listeners\\updateTimelineListener',
      1 => 'App\\Listeners\\updateGradeCatListener',
    ),
  ),
);