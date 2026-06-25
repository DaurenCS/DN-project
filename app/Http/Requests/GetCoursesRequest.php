<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCoursesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer|min:1|max:100',
            'page'     => 'integer|min:1',
        ];
    }
}
