<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|string[]>>
     */
    public function rules(): array
    {
        return [
            'rxcui' => ['required', 'string'],
        ];
    }
}
