<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;

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
                'url',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value)) {
                        return;
                    }

                    $scheme = parse_url(strtolower(trim($value)), PHP_URL_SCHEME);

                    // Block dangerous URL schemes that could execute code
                    $dangerousSchemes = ['javascript', 'data', 'vbscript', 'file', 'about'];

                    if ($scheme && in_array($scheme, $dangerousSchemes, true)) {
                        $fail('The :attribute field contains an unsafe URL scheme.');
                    }
                },
            ],
        ];
    }
}
