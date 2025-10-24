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
        /** @var UserMedication $medication */
        $medication = $this->resource;

        return [
            'id' => $medication->id,
            'rxcui' => $medication->rxcui,
            'drug_name' => $medication->drug_name,
            'base_names' => $medication->base_names ?? [],
            'dose_form_group_names' => $medication->dose_form_group_names ?? [],
            'added_at' => $medication->created_at?->toIso8601String(),
        ];
    }
}
