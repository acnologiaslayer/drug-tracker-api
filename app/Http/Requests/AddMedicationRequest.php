<?php<?php



namespace App\Http\Requests;namespace App\Http\Requests;



use Illuminate\Foundation\Http\FormRequest;use Illuminate\Foundation\Http\FormRequest;



class AddMedicationRequest extends FormRequestclass AddMedicationRequest extends FormRequest

{{

    public function authorize(): bool    public function authorize(): bool

    {    {

        return true;        return true;

    }    }



    /**    /**

     * @return array<string, array<int, string|string[]>>     * @return array<string, array<int, string|string[]>>

     */     */

    public function rules(): array    public function rules(): array

    {    {

        return [        return [

            'rxcui' => ['required', 'string'],            'rxcui' => ['required', 'string'],

        ];        ];

    }    }

}}

