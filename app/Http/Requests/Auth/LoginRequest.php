<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:4'],
            'device_name' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email обязателен',
            'email.email'       => 'Неверный формат email',
            'password.required' => 'Пароль обязателен',
            'password.min'      => 'Пароль минимум 4 символов',
        ];
    }
}
