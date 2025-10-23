<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'rxcui' => $this['rxcui'],
            'name' => $this['name'],
            'ingredient_base_names' => $this['ingredient_base_names'] ?? [],
            'dosage_forms' => $this['dosage_forms'] ?? [],
        ];
    }
}
