<?php

namespace App\Policies;

use App\Models\Demo;
use App\Models\User;

class DemoPolicy
{
    public function confirm(User $user, Demo $demo): bool
    {
        return $demo->user_id === $user->id;
    }

    public function retryAnalysis(User $user, Demo $demo): bool
    {
        return $demo->user_id === $user->id;
    }
}
