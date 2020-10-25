<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
    ),
    'App\\Events\\UserGradeEvent' => 
    array (
      0 => 'App\\Listerners\\UserGradeListener',
    ),
    'App\\Events\\AssignmentLessonEvent' => 
    array (
      0 => 'App\\Listerners\\CreateAssignmentLessonListener',
    ),
    'App\\Events\\QuizLessonEvent' => 
    array (
      0 => 'App\\Listerners\\CreateQuizLessonListener',
    ),
  ),
);