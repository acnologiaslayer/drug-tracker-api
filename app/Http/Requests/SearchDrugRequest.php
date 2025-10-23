<?php<?php



namespace App\Http\Requests;namespace App\Http\Requests;



use Illuminate\Foundation\Http\FormRequest;use Illuminate\Foundation\Http\FormRequest;



class SearchDrugRequest extends FormRequestclass SearchDrugRequest extends FormRequest

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

            'drug_name' => ['required', 'string', 'min:2'],            'drug_name' => ['required', 'string', 'min:2'],

        ];        ];

    }    }

}}

