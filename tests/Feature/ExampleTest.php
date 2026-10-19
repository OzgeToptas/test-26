<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_health_endpoint_does_not_request_the_external_api(): void
    {
        Http::preventStrayRequests();

        $this->get('/up')->assertOk();

        Http::assertNothingSent();
    }
}
