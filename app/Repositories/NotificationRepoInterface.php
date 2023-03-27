<?php

namespace App\Repositories;

interface NotificationRepoInterface
{
    public function sendNotify($users,$reqNot);
}
