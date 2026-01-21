<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Intervention\Image\Decoders\Base64ImageDecoder;
use Intervention\Image\Decoders\DataUriImageDecoder;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ValidFaviconData implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if it's a data URI
        if (preg_match('/^data:image\/(ico|x-icon|vnd\.microsoft\.icon);base64,/', $value)) {
            // ICO format detected, validate it can be read
            try {
                Image::read($value, [
                    Base64ImageDecoder::class,
                    DataUriImageDecoder::class,
                ]);
            } catch (Throwable) {
                $fail("Invalid ICO file for $attribute. Please provide a valid favicon.ico file.");
            }

            return;
        }

        // For other image formats (PNG, etc.), validate normally
        // but note that ICO is preferred
        try {
            Image::read($value, [
                Base64ImageDecoder::class,
                DataUriImageDecoder::class,
            ]);
        } catch (Throwable) {
            $fail("Invalid image for $attribute. ICO format is recommended for favicons.");
        }
    }
}
