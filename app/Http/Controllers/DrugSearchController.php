<?php<?php



namespace App\Http\Controllers;namespace App\Http\Controllers;



use App\Http\Requests\SearchDrugRequest;use App\Http\Requests\SearchDrugRequest;

use App\Http\Resources\DrugSearchResource;use App\Http\Resources\DrugSearchResource;

use App\Services\DrugSearchService;use App\Services\DrugSearchService;

use Illuminate\Http\JsonResponse;use Illuminate\Http\JsonResponse;

use Symfony\Component\HttpFoundation\Response;use Symfony\Component\HttpFoundation\Response;



class DrugSearchController extends Controllerclass DrugSearchController extends Controller

{{

    public function __construct(private readonly DrugSearchService $searchService)    public function __construct(private readonly DrugSearchService $searchService)

    {    {

    }    }



    public function __invoke(SearchDrugRequest $request): JsonResponse    public function __invoke(SearchDrugRequest $request): JsonResponse

    {    {

        $results = $this->searchService->search($request->string('drug_name')->toString());        $results = $this->searchService->search($request->string('drug_name')->toString());



        return response()->json([        return response()->json([

            'success' => true,            'success' => true,

            'data' => DrugSearchResource::collection($results),            'data' => DrugSearchResource::collection($results),

        ], Response::HTTP_OK);        ], Response::HTTP_OK);

    }    }

}}

