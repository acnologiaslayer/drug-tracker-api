<?php<?php



namespace App\Http\Requests\Auth;namespace App\Http\Requests\Auth;



use Illuminate\Foundation\Http\FormRequest;use Illuminate\Foundation\Http\FormRequest;



class RegisterRequest extends FormRequestclass RegisterRequest extends FormRequest

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

            'name' => ['required', 'string', 'min:2', 'max:255'],            'name' => ['required', 'string', 'min:2', 'max:255'],

            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            'password' => ['required', 'string', 'min:8'],            'password' => ['required', 'string', 'min:8'],

        ];        ];

    }    }

}}

