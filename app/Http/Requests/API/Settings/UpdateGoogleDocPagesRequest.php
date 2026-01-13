<?php

namespace App\Http\Requests\API\Settings;

use App\Http\Requests\API\Request;

/**
 * @property-read array<int, array{title: string, slug: string, embed_url: string, default_back_url: ?string}> $pages
 */
class UpdateGoogleDocPagesRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'pages' => 'required|array',
            'pages.*.title' => 'required|string|max:200',
            'pages.*.slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'pages.*.embed_url' => 'required|url|max:1000',
            'pages.*.default_back_url' => 'nullable|string|max:500',
        ];
    }

    /** @inheritdoc */
    public function messages(): array
    {
        return [
            'pages.*.slug.regex' => 'El slug debe contener solo letras minúsculas, números y guiones (ej: terminos-y-condiciones).',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ensure slugs are unique
        $pages = $this->pages ?? [];
        $slugs = array_column($pages, 'slug');

        if (count($slugs) !== count(array_unique($slugs))) {
            $this->merge([
                'pages' => array_map(function ($page, $index) use ($slugs) {
                    $slug = $page['slug'] ?? '';
                    $count = array_count_values($slugs)[$slug] ?? 0;

                    if ($count > 1 && $slug) {
                        $page['_duplicate_slug'] = true;
                    }

                    return $page;
                }, $pages, array_keys($pages))
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $pages = $this->pages ?? [];
            $slugs = array_column($pages, 'slug');

            if (count($slugs) !== count(array_unique($slugs))) {
                $validator->errors()->add('pages', 'Los slugs deben ser únicos.');
            }
        });
    }
}
