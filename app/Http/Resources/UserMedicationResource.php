<?php

namespace App\Http\Resources;

use App\Models\UserMedication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserMedication */
class UserMedicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rxcui' => $this->rxcui,
            'drug_name' => $this->drug_name,
            'base_names' => $this->base_names,
            'dose_form_group_names' => $this->dose_form_group_names,
            'added_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
