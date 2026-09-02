<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'id' => ['required'],
        ];
    }
}
