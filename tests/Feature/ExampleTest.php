<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_root_redirects(): void
    {
        // The marketing root redirects guests to login/dashboard depending on
        // session state — the only invariant is that it doesn't 5xx.
        $this->get('/')->assertStatus(302);
    }
}
