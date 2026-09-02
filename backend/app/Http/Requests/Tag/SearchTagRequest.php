<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class SearchTagRequest extends FormRequest
{
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
            'keywords' => ['nullable', 'string'],
            'item_number' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'in:name,count,created_at,updated_at'],
        ];
    }
}
