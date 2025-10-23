<?php<?php



namespace App\Http\Resources;namespace App\Http\Resources;



use App\Models\UserMedication;use App\Models\UserMedication;

use Illuminate\Http\Request;use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;use Illuminate\Http\Resources\Json\JsonResource;



/** @mixin UserMedication *//** @mixin UserMedication */

class UserMedicationResource extends JsonResourceclass UserMedicationResource extends JsonResource

{{

    /**    /**

     * @return array<string, mixed>     * Transform the resource into an array.

     */     *

    public function toArray(Request $request): array     * @return array<string, mixed>

    {     */

        return [    public function toArray(Request $request): array

            'id' => $this->id,    {

            'rxcui' => $this->rxcui,        return [

            'drug_name' => $this->drug_name,            'id' => $this->id,

            'base_names' => $this->base_names,            'rxcui' => $this->rxcui,

            'dose_form_group_names' => $this->dose_form_group_names,            'drug_name' => $this->drug_name,

            'added_at' => $this->created_at?->toIso8601String(),            'base_names' => $this->base_names,

        ];            'dose_form_group_names' => $this->dose_form_group_names,

    }            'added_at' => $this->created_at?->toIso8601String(),

}        ];

    }
}
