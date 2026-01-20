<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Values\CompositeToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AnonymousSessionController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        if (!config('koel.misc.allow_anonymous')) {
            abort(403, 'Anonymous sessions are not allowed');
        }

        $compositeToken = $this->loginAnonymously($request);

        return response()->json($compositeToken->toArray());
    }

    private function loginAnonymously(Request $request): CompositeToken
    {
        $clientIp = $request->ip();
        $anonymousEmail = $this->generateAnonymousEmail($clientIp);

        // Find or create anonymous user
        $user = $this->userRepository->findFirstWhere('email', $anonymousEmail);

        if (!$user) {
            $user = User::create([
                'email' => $anonymousEmail,
                'name' => 'Anonymous User',
                'password' => Hash::make(User::ANONYMOUS_PASSWORD),
                'organization_id' => Organization::default()->id,
            ]);

            $user->syncRoles('user');
        }

        return $this->auth->logUserIn($user);
    }

    private function generateAnonymousEmail(string $clientIp): string
    {
        $hash = hash('crc32', $clientIp . config('app.key'));
        $shortHash = substr($hash, 0, 8);

        return "{$shortHash}@" . User::ANONYMOUS_USER_DOMAIN;
    }
}
