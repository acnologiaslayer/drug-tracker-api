<?php<?php



namespace App\Http\Requests\Auth;namespace App\Http\Requests\Auth;



use Illuminate\Foundation\Http\FormRequest;use Illuminate\Foundation\Http\FormRequest;



class LoginRequest extends FormRequestclass LoginRequest extends FormRequest

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

            'email' => ['required', 'string', 'email'],            'email' => ['required', 'string', 'email'],

            'password' => ['required', 'string'],            'password' => ['required', 'string'],

        ];        ];

    }    }

}}

