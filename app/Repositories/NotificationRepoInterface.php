<?php

namespace App\Repositories;

interface NotificationRepoInterface
{
    public function sendNotify($users,$message,$item_id,$type,$item_type);
}
