<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read bool $owned
 */
class SongListRequest extends Request
{
    public function rules(): array
    {
        return [
            'owned' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'owned' => filter_var($this->owned ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
