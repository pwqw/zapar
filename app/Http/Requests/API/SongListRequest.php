<?php

namespace App\Http\Requests\API;

/**
 * @property-read string $order
 * @property-read string $sort
 * @property-read bool $owned
 */
class SongListRequest extends Request
{
    protected function prepareForValidation(): void
    {
        if ($this->has('owned')) {
            $this->merge([
                'owned' => filter_var($this->input('owned'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
