<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\CreateAnonymousSessionRequest;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AnonymousSessionController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
        private readonly UserRepository $userRepository,
        private readonly ConsentService $consentService,
    ) {
    }

    public function create(CreateAnonymousSessionRequest $request): JsonResponse
    {
        if (!config('koel.misc.allow_anonymous')) {
            abort(403, 'Anonymous sessions are not allowed');
        }

        $user = $this->findOrCreateAnonymousUser($request);

        $this->consentService->recordConsent($user, $request);

        return response()->json($this->auth->logUserIn($user)->toArray());
    }

    private function findOrCreateAnonymousUser(Request $request): User
    {
        $this->setLocaleFromRequest($request);

        $clientIp = $request->ip();
        $anonymousEmail = $this->generateAnonymousEmail($clientIp);

        $user = $this->userRepository->findFirstWhere('email', $anonymousEmail);

        if (!$user) {
            $user = User::create([
                'email' => $anonymousEmail,
                'name' => __('auth.anonymous_user_name'),
                'password' => Hash::make(User::ANONYMOUS_PASSWORD),
                'organization_id' => Organization::default()->id,
            ]);

            $user->syncRoles('user');
        }

        return $user;
    }

    private function setLocaleFromRequest(Request $request): void
    {
        $locale = $request->input('locale');

        if (is_string($locale) && in_array($locale, ['en', 'es'], true)) {
            app()->setLocale($locale);
        }
    }

    private function generateAnonymousEmail(string $clientIp): string
    {
        $hash = hash('crc32', $clientIp . config('app.key'));
        $shortHash = substr($hash, 0, 8);

        return "{$shortHash}@" . User::ANONYMOUS_USER_DOMAIN;
    }
}
