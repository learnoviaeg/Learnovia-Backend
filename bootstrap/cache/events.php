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
    'App\\Events\\CreatedGradeCatEvent' => 
    array (
      0 => 'App\\Listeners\\IncreaseIndexListener',
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
    'App\\Events\\TakeAttendanceEvent' => 
    array (
      0 => 'App\\Listeners\\CalculateSessionsListener',
    ),
    'App\\Events\\SessionCreatedEvent' => 
    array (
      0 => 'App\\Listeners\\LogsCreatedListener',
    ),
    'App\\Events\\GraderSetupEvent' => 
    array (
      0 => 'App\\Listeners\\RefreshGraderSetupListener',
    ),
    'App\\Events\\UserGradesEditedEvent' => 
    array (
      0 => 'App\\Listeners\\CalculateUserGradesListener',
    ),
    'App\\Events\\AssignmentCreatedEvent' => 
    array (
      0 => 'App\\Listeners\\AssignmentGradeCategoryListener',
    ),
    'App\\Events\\GradeCalculatedEvent' => 
    array (
      0 => 'App\\Listeners\\LetterPercentageListener',
    ),
    'App\\Events\\CreateCourseItemEvent' => 
    array (
      0 => 'App\\Listeners\\SendNotificationListener',
    ),
  ),
);