<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserConsentLog;
use Illuminate\Http\Request;

class ConsentService
{
    /**
     * Update the user's consent timestamps and create UserConsentLog entries.
     */
    public function recordConsent(User $user, Request $request): void
    {
        $now = now();

        $user->update([
            'terms_accepted_at' => $now,
            'privacy_accepted_at' => $now,
            'age_verified_at' => $now,
        ]);

        $this->logConsent($user->id, $request);
    }

    private function logConsent(int $userId, Request $request): void
    {
        $consentTypes = ['terms', 'privacy', 'age_verification'];

        foreach ($consentTypes as $type) {
            UserConsentLog::create([
                'user_id' => $userId,
                'consent_type' => $type,
                'version' => config('app.legal_version', '1.0'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accepted' => true,
            ]);
        }
    }
}
