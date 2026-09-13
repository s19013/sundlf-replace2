<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoRequest extends FormRequest
{
    /** Determine whether the request may proceed to validation. */
    public function authorize(): bool
    {
        return true;
    }

    /** Copy the route memo ID into the validation data. */
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    /**
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'stars' => ['nullable', 'integer', 'min:0', 'max:5'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],
            'fetched_at' => ['required', 'date'],
        ];
    }
}
