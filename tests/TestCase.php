<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Override actingAs to use Sanctum for API guard
     */
    public function actingAs($user, $guard = null)
    {
        if ($guard === 'api' || $guard === 'sanctum') {
            Sanctum::actingAs($user, ['*']);
            return $this;
        }
        
        return parent::actingAs($user, $guard);
    }
}
