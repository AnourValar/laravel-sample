<?php

namespace Tests\Feature;

use Tests\TestCase;

class EcosystemTest extends TestCase
{
    /**
     * @return void
     */
    public function test_config(): void
    {
        $this->get('/')->assertStatus(404);
        $this->get('/web')->assertStatus(404);
        $this->get('/web/')->assertStatus(404);
        $this->get('/web/redirect')->assertStatus(302);
    }
}
