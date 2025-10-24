<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/** @mixin array<string, mixed> */
class DrugSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = (array) $this->resource;

        return [
            'rxcui' => Arr::get($data, 'rxcui', ''),
            'name' => Arr::get($data, 'name', ''),
            'ingredient_base_names' => Arr::get($data, 'ingredient_base_names', []),
            'dosage_forms' => Arr::get($data, 'dosage_forms', []),
        ];
    }
}
