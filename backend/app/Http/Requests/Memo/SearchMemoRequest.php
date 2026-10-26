<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class SearchMemoRequest extends FormRequest
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
            'keyword' => ['nullable', 'string'],
            'item_number' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'in:updated_at,created_at,title,count,random'],
            'target' => ['nullable', 'string', 'in:title,body,both'],
            'is_tag_not_attached' => ['nullable', 'boolean'],
            'exact_match_tags' => ['nullable', 'array'],
            'exact_match_tags.*' => ['integer'],
            'partial_match_tags' => ['nullable', 'array'],
            'partial_match_tags.*' => ['integer'],
            'exclusion_match_tags' => ['nullable', 'array'],
            'exclusion_match_tags.*' => ['integer'],
            'stars' => ['nullable', 'integer', 'min:0', 'max:5'],
            'created_at_range_start' => ['nullable', 'date'],
            'created_at_range_end' => ['nullable', 'date'],
            'updated_at_range_start' => ['nullable', 'date'],
            'updated_at_range_end' => ['nullable', 'date'],
            'is_in_trashbox' => ['nullable', 'boolean'],
        ];
    }
}
