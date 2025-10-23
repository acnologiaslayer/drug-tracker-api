<?php<?php



namespace App\Http\Resources;namespace App\Http\Resources;



use Illuminate\Http\Request;use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;use Illuminate\Http\Resources\Json\JsonResource;



/** @mixin array<string, mixed> *//** @mixin array<string, mixed> */

class DrugSearchResource extends JsonResourceclass DrugSearchResource extends JsonResource

{{

    /**    /**

     * @return array<string, mixed>     * Transform the resource into an array.

     */     *

    public function toArray(Request $request): array     * @return array<string, mixed>

    {     */

        return [    public function toArray(Request $request): array

            'rxcui' => $this['rxcui'],    {

            'name' => $this['name'],        return [

            'ingredient_base_names' => $this['ingredient_base_names'] ?? [],            'rxcui' => $this['rxcui'],

            'dosage_forms' => $this['dosage_forms'] ?? [],            'name' => $this['name'],

        ];            'ingredient_base_names' => $this['ingredient_base_names'] ?? [],

    }            'dosage_forms' => $this['dosage_forms'] ?? [],

}        ];

    }
}
