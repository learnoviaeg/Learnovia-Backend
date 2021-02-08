<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'Illuminate\\Auth\\Events\\Registered' => 
    array (
      0 => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
    ),
    'App\\Events\\UserGradeEvent' => 
    array (
      0 => 'App\\Listeners\\UserGradeListener',
    ),
    'App\\Events\\MassLogsEvent' => 
    array (
      0 => 'App\\Listeners\\MassLogsListener',
    ),
  ),
);