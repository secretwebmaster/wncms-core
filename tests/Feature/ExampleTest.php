<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Wncms\Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    // test if environment is testing
    public function test_environment_is_testing()
    {
        $this->assertEquals('testing', $this->app->environment());
    }
}
