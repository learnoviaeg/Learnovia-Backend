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
  ),
);