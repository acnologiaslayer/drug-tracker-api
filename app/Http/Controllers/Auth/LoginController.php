<?php<?php



namespace App\Http\Controllers\Auth;namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\LoginRequest;use App\Http\Requests\Auth\LoginRequest;

use Illuminate\Http\JsonResponse;use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Auth;use Illuminate\Support\Facades\Auth;

use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpFoundation\Response;



class LoginController extends Controllerclass LoginController extends Controller

{{

    public function __invoke(LoginRequest $request): JsonResponse    public function __invoke(LoginRequest $request): JsonResponse

    {    {

        if (! Auth::attempt($request->only('email', 'password'))) {        if (! Auth::attempt($request->only('email', 'password'))) {

            return response()->json([            return response()->json([

                'success' => false,                'success' => false,

                'message' => 'Invalid credentials.',                'message' => 'Invalid credentials.',

            ], Response::HTTP_UNAUTHORIZED);            ], Response::HTTP_UNAUTHORIZED);

        }        }



        $user = $request->user();        $user = $request->user();

        $token = $user->createToken('api')->plainTextToken;        $token = $user->createToken('api')->plainTextToken;



        return response()->json([        return response()->json([

            'success' => true,            'success' => true,

            'message' => 'Login successful.',            'message' => 'Login successful.',

            'data' => [            'data' => [

                'access_token' => $token,                'access_token' => $token,

                'token_type' => 'Bearer',                'token_type' => 'Bearer',

            ],            ],

        ]);        ]);

    }    }

}}

