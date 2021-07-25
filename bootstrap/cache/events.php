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
    'App\\Events\\GradeAttemptEvent' => 
    array (
      0 => 'App\\Listeners\\GradeAttemptItemlistener',
    ),
    'App\\Events\\RefreshGradeTreeEvent' => 
    array (
      0 => 'App\\Listeners\\RefreshGradeTreeListener',
    ),
  ),
);