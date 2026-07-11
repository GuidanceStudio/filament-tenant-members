<?php

namespace Guidance\FilamentTenantMembers\Models;

use BackedEnum;
use Carbon\CarbonInterval;
use Guidance\FilamentTenantMembers\Concerns\HasConfigurableKeyType;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['token', 'organization_id', 'user_id', 'email', 'role', 'expires_at', 'accepted_at'])]
class OrganizationInvite extends Model
{
    use HasConfigurableKeyType;

    protected function casts(): array
    {
        return [
            'role' => FilamentTenantMembers::getRoleEnum(),
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function scopeByToken(Builder $query, string $token): Builder
    {
        return $query->where('token', $token);
    }

    public function accept(Authenticatable $user): void
    {
        $this->update(['accepted_at' => now()]);

        $this->organization->users()->syncWithoutDetaching([
            $user->getAuthIdentifier() => [
                'role' => $this->role instanceof BackedEnum ? $this->role->value : $this->role,
            ],
        ]);
    }

    /**
     * Mint a single-use, TTL-bounded invite that TARGETS a specific existing
     * user — the admin-provisioning ("set your password" / password-reset) flow,
     * as opposed to the email-only self-serve invite. The invitee is identified
     * by `email` (matching {@see matchesUser}); `user_id` also points at the
     * target so a cascade delete of the user reaps their pending links.
     *
     * The link is single-use + time-boxed by the same `pending()` scope every
     * consumer already relies on (whereNull accepted_at AND expires_at > now):
     * consume it with {@see completePasswordSet}, which stamps `accepted_at`.
     *
     * @param  \DateInterval|null  $expiresIn  TTL; defaults to the configured
     *         `invite_expires_days`. Pass a shorter interval (e.g.
     *         CarbonInterval::hours(48)) for a tighter reset window.
     */
    public static function mintForUser(
        Authenticatable $user,
        Model $organization,
        BackedEnum|string $role,
        ?\DateInterval $expiresIn = null,
    ): static {
        $expiresIn ??= CarbonInterval::days(FilamentTenantMembers::getInviteExpiresDays());

        return static::create([
            'token' => (string) Str::uuid(),
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getAuthIdentifier(),
            'email' => $user->getAttribute('email'),
            'role' => $role instanceof BackedEnum ? $role->value : $role,
            'expires_at' => now()->add($expiresIn),
            'accepted_at' => null,
        ]);
    }

    /**
     * Consume a targeted invite by setting the invitee's OWN password and joining
     * them to the organization. Reuses {@see accept} for the single-use stamp +
     * membership sync, so the same `pending()` scope makes a second consume a
     * no-op (the caller sees the invite is no longer pending).
     *
     * The plaintext is assigned to the user model's `password` attribute and
     * persisted — the consumer's model is expected to hash it on assignment
     * (Laravel's `hashed` cast, the framework default). The secret is NEVER
     * logged or returned.
     *
     * @return Authenticatable  the target user, with the new password persisted.
     */
    public function completePasswordSet(string $plainPassword): Authenticatable
    {
        $userModel = FilamentTenantMembers::getUserModel();

        /** @var Authenticatable&Model $user */
        $user = $userModel::query()->where('email', $this->email)->firstOrFail();

        $user->setAttribute('password', $plainPassword);
        $user->save();

        $this->accept($user);

        return $user;
    }

    public function matchesUser(Authenticatable $user): bool
    {
        return strcasecmp((string) $user->email, $this->email) === 0;
    }

    public function isResendable(): bool
    {
        $cooldownMinutes = FilamentTenantMembers::getResendCooldownMinutes();

        return $this->updated_at->addMinutes($cooldownMinutes)->isPast();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(FilamentTenantMembers::getOrganizationModel());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(FilamentTenantMembers::getUserModel());
    }
}
