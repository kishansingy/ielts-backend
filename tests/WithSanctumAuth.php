<?php

namespace Tests;

use Laravel\Sanctum\Sanctum;

trait WithSanctumAuth
{
    /**
     * Act as a user with Sanctum authentication
     */
    protected function actingAsSanctum($user)
    {
        Sanctum::actingAs($user, ['*']);
        return $this;
    }
}
