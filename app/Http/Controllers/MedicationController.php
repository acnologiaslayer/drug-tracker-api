<?php<?php



namespace App\Http\Controllers;namespace App\Http\Controllers;



use App\Exceptions\DuplicateMedicationException;use App\Exceptions\DuplicateMedicationException;

use App\Exceptions\InvalidRxcuiException;use App\Exceptions\InvalidRxcuiException;

use App\Http\Requests\AddMedicationRequest;use App\Http\Requests\AddMedicationRequest;

use App\Http\Resources\UserMedicationResource;use App\Http\Resources\UserMedicationResource;

use App\Services\MedicationService;use App\Services\MedicationService;

use Illuminate\Http\JsonResponse;use Illuminate\Http\JsonResponse;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpFoundation\Response;



class MedicationController extends Controllerclass MedicationController extends Controller

{{

    public function __construct(private readonly MedicationService $medicationService)    public function __construct(private readonly MedicationService $medicationService)

    {    {

    }    }



    public function index(): AnonymousResourceCollection    public function index(): AnonymousResourceCollection

    {    {

        $medications = $this->medicationService->listMedications(request()->user()->id);        $medications = $this->medicationService->listMedications(request()->user()->id);



        return UserMedicationResource::collection($medications);        return UserMedicationResource::collection($medications);

    }    }



    public function store(AddMedicationRequest $request): JsonResponse    public function store(AddMedicationRequest $request): JsonResponse

    {    {

        try {        try {

            $medication = $this->medicationService->addMedication(            $medication = $this->medicationService->addMedication(

                request()->user()->id,                request()->user()->id,

                $request->string('rxcui')->toString()                $request->string('rxcui')->toString()

            );            );

        } catch (InvalidRxcuiException $exception) {        } catch (InvalidRxcuiException $exception) {

            return response()->json([            return response()->json([

                'success' => false,                'success' => false,

                'message' => $exception->getMessage(),                'message' => $exception->getMessage(),

            ], Response::HTTP_BAD_REQUEST);            ], Response::HTTP_BAD_REQUEST);

        } catch (DuplicateMedicationException $exception) {        } catch (DuplicateMedicationException $exception) {

            return response()->json([            return response()->json([

                'success' => false,                'success' => false,

                'message' => $exception->getMessage(),                'message' => $exception->getMessage(),

            ], Response::HTTP_CONFLICT);            ], Response::HTTP_CONFLICT);

        }        }



        return response()->json([        return response()->json([

            'success' => true,            'success' => true,

            'message' => 'Medication added successfully.',            'message' => 'Medication added successfully.',

            'data' => new UserMedicationResource($medication),            'data' => new UserMedicationResource($medication),

        ], Response::HTTP_CREATED);        ], Response::HTTP_CREATED);

    }    }



    public function destroy(string $rxcui): JsonResponse    public function destroy(string $rxcui): JsonResponse

    {    {

        $deleted = $this->medicationService->deleteMedication(request()->user()->id, $rxcui);        $deleted = $this->medicationService->deleteMedication(request()->user()->id, $rxcui);



        if (! $deleted) {        if (! $deleted) {

            return response()->json([            return response()->json([

                'success' => false,                'success' => false,

                'message' => 'Medication not found.',                'message' => 'Medication not found.',

            ], Response::HTTP_NOT_FOUND);            ], Response::HTTP_NOT_FOUND);

        }        }



        return response()->json([        return response()->json([

            'success' => true,            'success' => true,

            'message' => 'Medication removed successfully.',            'message' => 'Medication removed successfully.',

        ], Response::HTTP_OK);        ], Response::HTTP_OK);

    }    }

}}

