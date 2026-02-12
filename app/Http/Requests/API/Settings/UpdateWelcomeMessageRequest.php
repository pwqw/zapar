<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\SafeUrlOrInternalRoute;

/**
 * @property-read string $message
 * @property-read array<int, array{name: string, url: string}> $variables
 */
class UpdateWelcomeMessageRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'message' => 'required|string|max:5000',
            'variables' => 'sometimes|array',
            'variables.*.name' => 'required|string|max:200',
            'variables.*.url' => [
                'required',
                'string',
                new SafeUrlOrInternalRoute(),
            ],
        ];
    }
}
