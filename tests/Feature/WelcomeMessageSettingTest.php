<?php

namespace Tests\Feature;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class WelcomeMessageSettingTest extends TestCase
{
    #[Test]
    public function saveWelcomeMessageWithoutVariables(): void
    {
        $message = 'Welcome to our platform!';

        $this->putAs('/api/settings/welcome-message', ['message' => $message], create_admin())
            ->assertNoContent();

        self::assertSame($message, Setting::get('welcome_message'));
        self::assertSame([], Setting::get('welcome_message_variables') ?? []);
    }

    #[Test]
    public function saveWelcomeMessageWithVariables(): void
    {
        $message = 'Welcome! Visit {privacyPolicy} and {terms}';
        $variables = [
            ['name' => 'privacyPolicy', 'url' => 'https://example.com/privacy'],
            ['name' => 'terms', 'url' => 'https://example.com/terms'],
        ];

        $this->putAs('/api/settings/welcome-message', [
            'message' => $message,
            'variables' => $variables,
        ], create_admin())
            ->assertNoContent();

        self::assertSame($message, Setting::get('welcome_message'));
        self::assertSame($variables, Setting::get('welcome_message_variables'));
    }

    #[Test]
    public function nonAdminCannotSaveWelcomeMessage(): void
    {
        $this->putAs('/api/settings/welcome-message', ['message' => 'Welcome!'])
            ->assertForbidden();
    }

    #[Test]
    public function messageIsRequired(): void
    {
        $this->putAs('/api/settings/welcome-message', [], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function messageCannotExceedMaxLength(): void
    {
        $this->putAs('/api/settings/welcome-message', [
            'message' => str_repeat('x', 5001),
        ], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function variablesNameIsRequired(): void
    {
        $this->putAs('/api/settings/welcome-message', [
            'message' => 'Welcome!',
            'variables' => [
                ['url' => 'https://example.com/privacy'],
            ],
        ], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function variablesUrlIsRequired(): void
    {
        $this->putAs('/api/settings/welcome-message', [
            'message' => 'Welcome!',
            'variables' => [
                ['name' => 'privacy'],
            ],
        ], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function variablesUrlMustBeValid(): void
    {
        $this->putAs('/api/settings/welcome-message', [
            'message' => 'Welcome!',
            'variables' => [
                ['name' => 'privacy', 'url' => 'not-a-valid-url'],
            ],
        ], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function variablesUrlCanBeInternalRoute(): void
    {
        $message = 'Welcome! Visit {document}';
        $variables = [
            ['name' => 'document', 'url' => '/#/document/ejemplo'],
        ];

        $this->putAs('/api/settings/welcome-message', [
            'message' => $message,
            'variables' => $variables,
        ], create_admin())
            ->assertNoContent();

        self::assertSame($message, Setting::get('welcome_message'));
        self::assertSame($variables, Setting::get('welcome_message_variables'));
    }

    #[Test]
    public function variablesUrlCanBeInternalRouteStartingWithSlash(): void
    {
        $message = 'Welcome! Visit {home}';
        $variables = [
            ['name' => 'home', 'url' => '/dashboard'],
        ];

        $this->putAs('/api/settings/welcome-message', [
            'message' => $message,
            'variables' => $variables,
        ], create_admin())
            ->assertNoContent();

        self::assertSame($message, Setting::get('welcome_message'));
        self::assertSame($variables, Setting::get('welcome_message_variables'));
    }

    #[Test]
    public function variablesUrlCanMixExternalAndInternalRoutes(): void
    {
        $message = 'Welcome! Visit {privacy} and {document}';
        $variables = [
            ['name' => 'privacy', 'url' => 'https://example.com/privacy'],
            ['name' => 'document', 'url' => '/#/document/ejemplo'],
        ];

        $this->putAs('/api/settings/welcome-message', [
            'message' => $message,
            'variables' => $variables,
        ], create_admin())
            ->assertNoContent();

        self::assertSame($message, Setting::get('welcome_message'));
        self::assertSame($variables, Setting::get('welcome_message_variables'));
    }

    #[Test]
    public function variablesUrlCannotContainDangerousCharactersInInternalRoute(): void
    {
        $dangerousRoutes = [
            '/<script>alert(1)</script>',
            '/document"onclick="alert(1)',
            "/document'alert(1)'",
        ];

        foreach ($dangerousRoutes as $route) {
            $this->putAs('/api/settings/welcome-message', [
                'message' => 'Welcome!',
                'variables' => [
                    ['name' => 'test', 'url' => $route],
                ],
            ], create_admin())
                ->assertUnprocessable();
        }
    }

    #[Test]
    public function variablesNameCannotExceedMaxLength(): void
    {
        $this->putAs('/api/settings/welcome-message', [
            'message' => 'Welcome!',
            'variables' => [
                ['name' => str_repeat('x', 201), 'url' => 'https://example.com'],
            ],
        ], create_admin())
            ->assertUnprocessable();
    }

    #[Test]
    public function variablesUrlCannotBeDangerousScheme(): void
    {
        $dangerousUrls = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox(1)',
            'file:///etc/passwd',
        ];

        foreach ($dangerousUrls as $url) {
            $this->putAs('/api/settings/welcome-message', [
                'message' => 'Welcome!',
                'variables' => [
                    ['name' => 'test', 'url' => $url],
                ],
            ], create_admin())
                ->assertUnprocessable();
        }
    }
}
