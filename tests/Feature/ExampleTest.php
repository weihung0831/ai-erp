<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_redirects_to_component_showcase(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/components');
    }
}
