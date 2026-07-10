<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'billing_email', 'tax_document', 'password', 'oauth_provider', 'oauth_id', 'stripe_customer_id', 'profile_photo_path', 'background_photo_path', 'is_admin', 'is_barbershop', 'is_frozen', 'platform_subscription_exempt'])]
#[Hidden(['password', 'remember_token', 'profile_photo_path', 'background_photo_path', 'oauth_provider', 'oauth_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $appends = [
        'profile_photo_url',
        'is_administrator',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_barbershop' => 'boolean',
            'is_frozen' => 'boolean',
            'platform_subscription_exempt' => 'boolean',
            'stripe_connect_charges_enabled' => 'boolean',
            'stripe_connect_payouts_enabled' => 'boolean',
            'stripe_connect_details_submitted' => 'boolean',
        ];
    }

    public function isStripeConnectReady(): bool
    {
        return filled($this->stripe_connect_account_id)
            && (bool) $this->stripe_connect_charges_enabled;
    }

    /**
     * @return array{account_id: ?string, charges_enabled: bool, payouts_enabled: bool, details_submitted: bool, ready: bool}
     */
    public function stripeConnectStatus(): array
    {
        return [
            'account_id' => $this->stripe_connect_account_id,
            'charges_enabled' => (bool) $this->stripe_connect_charges_enabled,
            'payouts_enabled' => (bool) $this->stripe_connect_payouts_enabled,
            'details_submitted' => (bool) $this->stripe_connect_details_submitted,
            'ready' => $this->isStripeConnectReady(),
        ];
    }

    public function isExemptFromPlatformSubscription(): bool
    {
        return (bool) ($this->platform_subscription_exempt ?? false);
    }

    public function isFrozen(): bool
    {
        return (bool) $this->is_frozen;
    }

    public function isBarbershop(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        return (bool) $this->is_barbershop;
    }

    public function isBarbershopAccount(): bool
    {
        return (bool) $this->is_barbershop;
    }

    public static function countBarbershopAccounts(): int
    {
        return static::query()->barbershopAccounts()->count();
    }

    public function scopeBarbershopAccounts($query)
    {
        return $query->where('is_barbershop', true)->where('is_admin', false);
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_barbershop', false);
    }

    /**
     * @return list<string>
     */
    public static function ownerEmails(): array
    {
        return collect(config('admin.owner_emails', []))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->values()
            ->all();
    }

    public function scopeAdministrators($query)
    {
        $ownerEmails = static::ownerEmails();

        return $query->where(function ($query) use ($ownerEmails) {
            $query->where('is_admin', true);

            foreach ($ownerEmails as $email) {
                $query->orWhereRaw('LOWER(email) = ?', [$email]);
            }
        });
    }

    public function scopeRegularUsers($query)
    {
        $ownerEmails = static::ownerEmails();

        return $query->where('is_admin', false)
            ->when($ownerEmails !== [], function ($query) use ($ownerEmails) {
                $query->where(function ($query) use ($ownerEmails) {
                    foreach ($ownerEmails as $email) {
                        $query->whereRaw('LOWER(email) != ?', [$email]);
                    }
                });
            });
    }

    public function hasPublicProfile(): bool
    {
        if (! $this->isBarbershop() || blank($this->username) || $this->isFrozen()) {
            return false;
        }

        return $this->hasActivePlatformSubscription();
    }

    public function platformSubscription(): HasOne
    {
        return $this->hasOne(BarbershopPlatformSubscription::class, 'barbershop_user_id');
    }

    public function hasActivePlatformSubscription(): bool
    {
        if (! $this->isBarbershopAccount()) {
            return true;
        }

        if ($this->isExemptFromPlatformSubscription()) {
            return true;
        }

        return $this->platformSubscription?->isActive() ?? false;
    }

    public function isAdmin(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return in_array(
            strtolower($this->email),
            static::ownerEmails(),
            true,
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isAdministrator(): Attribute
    {
        return Attribute::get(fn (): bool => $this->isAdmin());
    }

    public function profileUrl(): ?string
    {
        if (! $this->hasPublicProfile()) {
            return null;
        }

        return url('/barbearias/'.$this->username);
    }

    public function storagePath(): string
    {
        return storage_path('app/users/'.$this->id);
    }

    public function subscriptionPlan(): HasOne
    {
        return $this->hasOne(ProfileSubscriptionPlan::class);
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(ProfileSubscription::class, 'creator_user_id');
    }

    public function profileSubscriptions(): HasMany
    {
        return $this->hasMany(ProfileSubscription::class, 'subscriber_user_id');
    }

    public function barbershopMembers(): HasMany
    {
        return $this->hasMany(BarbershopMembership::class, 'barbershop_user_id');
    }

    public function barbershopSignups(): HasMany
    {
        return $this->hasMany(BarbershopMembership::class, 'member_user_id');
    }

    public function servicePackages(): HasMany
    {
        return $this->hasMany(BarbershopServicePackage::class);
    }

    public function serviceAddons(): HasMany
    {
        return $this->hasMany(BarbershopServiceAddon::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(BarbershopEmployee::class, 'barbershop_user_id');
    }

    public function barbershopAppointments(): HasMany
    {
        return $this->hasMany(BarbershopAppointment::class, 'barbershop_user_id');
    }

    public function clientAppointments(): HasMany
    {
        return $this->hasMany(BarbershopAppointment::class, 'client_user_id');
    }

    public function servicePlanSubscribers(): HasMany
    {
        return $this->hasMany(ServicePlanSubscription::class, 'creator_user_id');
    }

    public function servicePlanSubscriptions(): HasMany
    {
        return $this->hasMany(ServicePlanSubscription::class, 'subscriber_user_id');
    }

    public function clientHaircutPhotos(): HasMany
    {
        return $this->hasMany(ClientHaircutPhoto::class);
    }

    public function sentBarbershopMessages(): HasMany
    {
        return $this->hasMany(BarbershopMessage::class, 'barbershop_user_id');
    }

    public function receivedBarbershopMessages(): HasMany
    {
        return $this->hasMany(BarbershopMessageRecipient::class, 'recipient_user_id');
    }

    public function receivedAdminBroadcastMessages(): HasMany
    {
        return $this->hasMany(AdminBroadcastMessageRecipient::class, 'recipient_user_id');
    }

    public function acrylicQrOrders(): HasMany
    {
        return $this->hasMany(AcrylicQrOrder::class);
    }

    public function subscribeUrl(): ?string
    {
        return $this->profileUrl();
    }

    public function primaryBarbershop(): ?User
    {
        if ($this->isBarbershop()) {
            return null;
        }

        $membership = $this->barbershopSignups()
            ->with('barbershop:id,username,is_barbershop,is_frozen')
            ->latest()
            ->first();

        $barbershop = $membership?->barbershop;

        if (
            $barbershop === null
            || ! $barbershop->isBarbershop()
            || $barbershop->is_frozen
            || blank($barbershop->username)
        ) {
            return null;
        }

        return $barbershop;
    }

    public static function defaultBarbershopPhotoUrl(): string
    {
        return '/images/icone-barbearia.png';
    }

    /**
     * @return Attribute<?string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->profile_photo_path) {
                return '/uploads/'.ltrim($this->profile_photo_path, '/');
            }

            if ($this->isBarbershop()) {
                return static::defaultBarbershopPhotoUrl();
            }

            return null;
        });
    }

    public function hasCustomProfilePhoto(): bool
    {
        return filled($this->profile_photo_path);
    }

    public function deleteProfilePhoto(): void
    {
        if (! $this->profile_photo_path) {
            return;
        }

        Storage::disk('public')->delete($this->profile_photo_path);

        $this->forceFill(['profile_photo_path' => null])->save();
    }

}
