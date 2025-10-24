<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    public function test_unauthenticated_returns_json_response(): void
    {
        $handler = app(Handler::class);
        $request = \Illuminate\Http\Request::create('/api/secure-endpoint', 'GET');
        $exception = new \Illuminate\Auth\AuthenticationException();

        $response = $this->invokeUnauthenticated($handler, $request, $exception);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertSame(401, $response->status());
        $this->assertSame([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], $response->getData(true));
    }

    private function invokeUnauthenticated(Handler $handler, \Illuminate\Http\Request $request, \Illuminate\Auth\AuthenticationException $exception): \Illuminate\Http\JsonResponse
    {
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('unauthenticated');
        $method->setAccessible(true);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $method->invoke($handler, $request, $exception);

        return $response;
    }
}
