<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class FetchMemoRequest extends FormRequest
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
            'fetched_at' => ['nullable', 'date'],
        ];
    }
}
