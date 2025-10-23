<?php<?php



namespace App\Http\Controllers\Auth;namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;use Illuminate\Http\JsonResponse;

use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpFoundation\Response;



class LogoutController extends Controllerclass LogoutController extends Controller

{{

    public function __invoke(): JsonResponse    public function __invoke(): JsonResponse

    {    {

        $user = request()->user();        $user = request()->user();



        if ($user !== null) {        if ($user !== null) {

            $user->currentAccessToken()?->delete();            $user->currentAccessToken()?->delete();

        }        }



        return response()->json([        return response()->json([

            'success' => true,            'success' => true,

            'message' => 'Successfully logged out.',            'message' => 'Successfully logged out.',

        ], Response::HTTP_OK);        ], Response::HTTP_OK);

    }    }

}}

