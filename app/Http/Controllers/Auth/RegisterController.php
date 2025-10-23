<?php<?php



namespace App\Http\Controllers\Auth;namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\RegisterRequest;use App\Http\Requests\Auth\RegisterRequest;

use App\Models\User;use App\Models\User;

use Illuminate\Http\JsonResponse;use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Hash;use Illuminate\Support\Facades\Hash;

use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpFoundation\Response;



class RegisterController extends Controllerclass RegisterController extends Controller

{{

    public function __invoke(RegisterRequest $request): JsonResponse    public function __invoke(RegisterRequest $request): JsonResponse

    {    {

        $user = User::create([        $user = User::create([

            'name' => $request->string('name')->toString(),            'name' => $request->string('name')->toString(),

            'email' => $request->string('email')->toString(),            'email' => $request->string('email')->toString(),

            'password' => Hash::make($request->string('password')->toString()),            'password' => Hash::make($request->string('password')->toString()),

        ]);        ]);



        $token = $user->createToken('api')->plainTextToken;        $token = $user->createToken('api')->plainTextToken;



        return response()->json([        return response()->json([

            'success' => true,            'success' => true,

            'message' => 'User registered successfully.',            'message' => 'User registered successfully.',

            'data' => [            'data' => [

                'user' => [                'user' => [

                    'id' => $user->id,                    'id' => $user->id,

                    'name' => $user->name,                    'name' => $user->name,

                    'email' => $user->email,                    'email' => $user->email,

                ],                ],

                'access_token' => $token,                'access_token' => $token,

                'token_type' => 'Bearer',                'token_type' => 'Bearer',

            ],            ],

        ], Response::HTTP_CREATED);        ], Response::HTTP_CREATED);

    }    }

}}

