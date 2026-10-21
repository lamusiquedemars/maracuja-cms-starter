<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Legacy tests exercise the former configuration adapter explicitly.
        config(['maracuja.module_manifest.enabled' => false]);
    }
}
