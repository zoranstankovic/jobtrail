<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render real HTML, and the layout calls @vite(). Without
        // this, every test would need a production build or a running dev
        // server. See the Laravel docs, "Disabling Vite in Tests".
        $this->withoutVite();
    }
}
