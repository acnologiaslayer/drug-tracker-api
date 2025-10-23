<?php

namespace App\Http\Controllers;

use App\Exceptions\RxNormApiException;
use App\Http\Requests\SearchDrugRequest;
use App\Http\Resources\DrugSearchResource;
use App\Services\DrugSearchService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DrugSearchController extends Controller
{
    public function __construct(private readonly DrugSearchService $searchService) {}

    public function __invoke(SearchDrugRequest $request): JsonResponse
    {
        try {
            $results = $this->searchService->search(
                $request->string('drug_name')->toString()
            );
        } catch (RxNormApiException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Drug search service is currently unavailable.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'success' => true,
            'data' => DrugSearchResource::collection($results)->toArray($request),
        ], Response::HTTP_OK);
    }
}
