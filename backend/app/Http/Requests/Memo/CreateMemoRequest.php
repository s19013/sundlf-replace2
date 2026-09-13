<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class CreateMemoRequest extends FormRequest
{
    /** Determine whether the request may proceed to validation. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'stars' => ['nullable', 'integer', 'min:0', 'max:5'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],
        ];
    }
}
