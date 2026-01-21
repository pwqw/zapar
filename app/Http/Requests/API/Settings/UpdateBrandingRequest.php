<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;
use App\Rules\ValidFaviconData;
use App\Rules\ValidImageData;
use Closure;
use Illuminate\Support\Facades\URL;

/**
 * @property-read string $name
 * @property-read ?string $logo
 * @property-read ?string $cover
 * @property-read ?string $favicon
 * @property-read ?string $description
 * @property-read ?string $og_image
 */
class UpdateBrandingRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        $validImageDataOrUrl = static function (string $attribute, mixed $value, Closure $fail): void {
            if (URL::isValidUrl($value)) {
                return;
            }

            (new ValidImageData())->validate($attribute, $value, $fail);
        };

        $validFaviconDataOrUrl = static function (string $attribute, mixed $value, Closure $fail): void {
            if (URL::isValidUrl($value)) {
                return;
            }

            (new ValidFaviconData())->validate($attribute, $value, $fail);
        };

        return [
            'name' => 'required|string',
            'logo' => ['sometimes', 'nullable', $validImageDataOrUrl],
            'cover' => ['sometimes', 'nullable', $validImageDataOrUrl],
            'favicon' => ['sometimes', 'nullable', $validFaviconDataOrUrl],
            'description' => 'sometimes|nullable|string|max:500',
            'og_image' => ['sometimes', 'nullable', $validImageDataOrUrl],
        ];
    }
}
