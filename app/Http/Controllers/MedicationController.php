<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateMedicationException;
use App\Exceptions\InvalidRxcuiException;
use App\Http\Requests\AddMedicationRequest;
use App\Http\Resources\UserMedicationResource;
use App\Services\MedicationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MedicationController extends Controller
{
    public function __construct(private readonly MedicationService $medicationService) {}

    public function index(): JsonResponse
    {
        $medications = $this->medicationService->listMedications(request()->user()->id);

        return response()->json([
            'success' => true,
            'data' => UserMedicationResource::collection($medications)->toArray(request()),
        ], Response::HTTP_OK);
    }

    public function store(AddMedicationRequest $request): JsonResponse
    {
        try {
            $medication = $this->medicationService->addMedication(
                request()->user()->id,
                $request->string('rxcui')->toString()
            );
        } catch (InvalidRxcuiException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (DuplicateMedicationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return response()->json([
            'success' => true,
            'message' => 'Medication added successfully.',
            'data' => new UserMedicationResource($medication),
        ], Response::HTTP_CREATED);
    }

    public function destroy(string $rxcui): JsonResponse
    {
        $deleted = $this->medicationService->deleteMedication(request()->user()->id, $rxcui);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Medication not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Medication removed successfully.',
        ], Response::HTTP_OK);
    }
}
