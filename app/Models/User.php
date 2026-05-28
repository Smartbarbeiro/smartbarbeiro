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

#[Fillable(['name', 'username', 'email', 'password', 'profile_photo_path', 'is_admin', 'is_barbershop', 'is_frozen'])]
#[Hidden(['password', 'remember_token', 'profile_photo_path'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function isFrozen(): bool
    {
        return (bool) $this->is_frozen;
    }

    public function isBarbershop(): bool
    {
        return (bool) $this->is_barbershop;
    }

    public function hasPublicProfile(): bool
    {
        return $this->isBarbershop() && filled($this->username) && ! $this->isFrozen();
    }

    public function isAdmin(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return in_array(
            strtolower($this->email),
            config('admin.owner_emails', []),
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

    public function subscribeUrl(): ?string
    {
        return $this->profileUrl();
    }

    /**
     * @return Attribute<?string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->profile_photo_path) {
                return null;
            }

            return Storage::disk('public')->url($this->profile_photo_path);
        });
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
