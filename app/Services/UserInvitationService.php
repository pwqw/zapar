<?php

namespace App\Services;

use App\Enums\Acl\Role;
use App\Exceptions\InvitationNotFoundException;
use App\Helpers\Uuid;
use App\Mail\UserInvite;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserInvitationService
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /** @return Collection<array-key, User> */
    public function invite(array $emails, Role $role, User $invitor): Collection
    {
        $role->assertAvailable();

        return DB::transaction(function () use ($emails, $role, $invitor) {
            return collect($emails)->map(fn ($email) => $this->inviteOne($email, $role, $invitor));
        });
    }

    public function getUserProspectByToken(string $token): User
    {
        return User::query()->where('invitation_token', $token)->firstOr(static function (): never {
            throw new InvitationNotFoundException();
        });
    }

    public function revokeByEmail(string $email): void
    {
        $user = $this->userRepository->findOneByEmail($email);
        throw_unless($user?->is_prospect, new InvitationNotFoundException());
        $user->delete();
    }

    private function inviteOne(string $email, Role $role, User $invitor): User
    {
        $role->assertAvailable();

        /** @var User $invitee */
        $invitee = $invitor->organization->users()->create([
            'name' => '',
            'email' => $email,
            'password' => '',
            'invited_by_id' => $invitor->id,
            'invitation_token' => Uuid::generate(),
            'invited_at' => now(),
        ]);

        $invitee->syncRoles($role);

        Mail::to($email)->queue(new UserInvite($invitee));

        return $invitee;
    }

    /**
     * @param array{ip_address?: string, user_agent?: string} $consentMeta
     */
    public function accept(string $token, string $name, string $password, array $consentMeta = []): User
    {
        $user = $this->getUserProspectByToken($token);
        $now = now();

        $user->update(attributes: [
            'name' => $name,
            'password' => Hash::make($password),
            'invitation_token' => null,
            'invitation_accepted_at' => $now,
            'terms_accepted_at' => $now,
            'privacy_accepted_at' => $now,
            'age_verified_at' => $now,
        ]);

        $this->logConsent($user, $consentMeta);

        return $user;
    }

    /**
     * @param array{ip_address?: string, user_agent?: string} $meta
     */
    private function logConsent(User $user, array $meta): void
    {
        $consentTypes = ['terms', 'privacy', 'age_verification'];

        foreach ($consentTypes as $type) {
            $user->consentLogs()->create([
                'consent_type' => $type,
                'version' => config('app.legal_version', '1.0'),
                'ip_address' => $meta['ip_address'] ?? null,
                'user_agent' => $meta['user_agent'] ?? null,
                'accepted' => true,
            ]);
        }
    }
}
