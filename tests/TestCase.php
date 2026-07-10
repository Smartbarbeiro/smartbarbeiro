<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['mercadopago.back_url' => 'https://example.com']);
        // Existing service-plan tests use platform Stripe without Connect accounts.
        config(['stripe.connect_enabled' => false]);
    }
}
