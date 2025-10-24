<?php

namespace App\Exceptions;

class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    /**
     * Convert authentication failures into a JSON 401 response for API clients.
     */
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception): \Illuminate\Http\JsonResponse
    {
        return new \Illuminate\Http\JsonResponse([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED);
    }
}
