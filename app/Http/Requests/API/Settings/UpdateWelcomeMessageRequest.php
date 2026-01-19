<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use Closure;

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
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (!is_string($value)) {
                        $fail('The :attribute must be a string.');
                        return;
                    }

                    $trimmedValue = trim($value);

                    // Allow internal routes (starting with / or /#/)
                    if (str_starts_with($trimmedValue, '/')) {
                        // Validate it's a safe internal route (no dangerous characters)
                        if (preg_match('/[<>"\']/', $trimmedValue)) {
                            $fail('The :attribute contains invalid characters for an internal route.');
                        }
                        return;
                    }

                    // For external URLs, validate they are proper URLs
                    if (!filter_var($trimmedValue, FILTER_VALIDATE_URL)) {
                        $fail('The :attribute must be a valid URL or an internal route starting with /.');
                        return;
                    }

                    // Block dangerous URL schemes that could execute code
                    $scheme = parse_url(strtolower($trimmedValue), PHP_URL_SCHEME);
                    $dangerousSchemes = ['javascript', 'data', 'vbscript', 'file', 'about'];

                    if ($scheme && in_array($scheme, $dangerousSchemes, true)) {
                        $fail('The :attribute field contains an unsafe URL scheme.');
                    }
                },
            ],
        ];
    }
}
