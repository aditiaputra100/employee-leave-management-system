<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Client;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure personal access client exists for testing token creation
        if (!Client::query()->whereNotNull('user_id')->where('personal_access_client', true)->first() &&
            !Client::query()->whereNull('user_id')->where('personal_access_client', true)->first()) {
            Artisan::call('passport:client', [
                '--personal' => true,
                '--no-interaction' => true,
            ]);
        }
    }
}

