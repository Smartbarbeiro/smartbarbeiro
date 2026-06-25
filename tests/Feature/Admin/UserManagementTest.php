<?php

namespace Tests\Feature\Admin;

use App\Models\BarbershopMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_admin_panel(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_users_with_subscriber_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->where('barbershopAccountsCount', 1)
                ->has('admins', 1)
                ->has('barbershops.data', 1)
                ->where('barbershops.data.0.subscribers_count', fn ($count) => is_int($count))
                ->where('barbershops.data.0.barbershop_members_count', fn ($count) => is_int($count))
                ->has('unassignedClients.data', 1));
    }

    public function test_admin_can_freeze_and_unfreeze_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.freeze', $user))
            ->assertRedirect()
            ->assertSessionHas('status', 'user-frozen');

        $this->assertTrue($user->fresh()->isFrozen());

        $this->actingAs($admin)
            ->patch(route('admin.users.freeze', $user))
            ->assertSessionHas('status', 'user-unfrozen');

        $this->assertFalse($user->fresh()->isFrozen());
    }

    public function test_frozen_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'frozen@example.com',
            'is_frozen' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'frozen@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_frozen_barbershop_public_profile_is_hidden(): void
    {
        $barbershop = User::factory()->create([
            'is_frozen' => true,
        ]);

        $this->get(route('profile.public', $barbershop->username))
            ->assertNotFound();
    }

    public function test_admin_cannot_freeze_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.freeze', $admin))
            ->assertForbidden();
    }


    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'New Name',
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => false,
            ])
            ->assertRedirect(route('admin.users.edit', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_can_exempt_barbershop_from_platform_subscription(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();

        $barbershop->platformSubscription()->update([
            'status' => \App\Models\BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $barbershop), [
                'name' => $barbershop->name,
                'username' => $barbershop->username,
                'email' => $barbershop->email,
                'is_admin' => false,
                'platform_subscription_exempt' => true,
            ])
            ->assertRedirect(route('admin.users.edit', $barbershop));

        $barbershop->refresh();

        $this->assertTrue($barbershop->isExemptFromPlatformSubscription());
        $this->assertTrue($barbershop->hasActivePlatformSubscription());
        $this->assertTrue($barbershop->hasPublicProfile());
    }

    public function test_admin_update_without_exempt_field_preserves_existing_exemption(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create([
            'platform_subscription_exempt' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $barbershop), [
                'name' => 'Nome atualizado',
                'username' => $barbershop->username,
                'email' => $barbershop->email,
                'is_admin' => false,
            ])
            ->assertRedirect(route('admin.users.edit', $barbershop));

        $this->assertTrue($barbershop->fresh()->isExemptFromPlatformSubscription());
    }

    public function test_exempt_barbershop_can_access_dashboard_without_platform_payment(): void
    {
        $barbershop = User::factory()->create([
            'platform_subscription_exempt' => true,
        ]);

        $barbershop->platformSubscription()->update([
            'status' => \App\Models\BarbershopPlatformSubscription::STATUS_PENDING,
        ]);

        $this->actingAs($barbershop)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_user_and_storage(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $path = 'profile-photos/'.$user->id.'/avatar.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        $user->update(['profile_photo_path' => $path]);

        File::ensureDirectoryExists($user->storagePath());
        File::put($user->storagePath().'/test.txt', 'data');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('public')->assertMissing($path);
        $this->assertFalse(File::isDirectory($user->storagePath()));
    }

    public function test_admin_account_is_not_barbershop_and_has_no_public_profile(): void
    {
        $admin = User::factory()->admin()->create([
            'username' => 'legacy-admin',
            'is_barbershop' => true,
        ]);

        $this->assertFalse($admin->isBarbershop());
        $this->assertFalse($admin->hasPublicProfile());
        $this->assertNull($admin->profileUrl());
    }

    public function test_promoting_user_to_admin_clears_barbershop_account(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $barbershop), [
                'name' => $barbershop->name,
                'username' => $barbershop->username,
                'email' => $barbershop->email,
                'is_admin' => true,
            ])
            ->assertRedirect(route('admin.users.edit', $barbershop));

        $barbershop->refresh();

        $this->assertTrue($barbershop->isAdmin());
        $this->assertFalse($barbershop->is_barbershop);
        $this->assertNull($barbershop->username);
        $this->assertFalse($barbershop->hasPublicProfile());
    }

    public function test_admin_dashboard_shows_barbershop_accounts_count(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create();
        User::factory()->create();
        User::factory()->customer()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('isAdmin', true)
                ->where('barbershopAccountsCount', 2));
    }

    public function test_owner_email_from_config_is_treated_as_admin(): void
    {
        config(['admin.owner_emails' => ['owner@example.com']]);

        $owner = User::factory()->create(['email' => 'owner@example.com']);

        $this->assertTrue($owner->isAdmin());

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_barbershop_lists_registered_clients(): void
    {
        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create(['name' => 'Cliente VIP']);

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('barbershops.data', 1)
                ->where('barbershops.data.0.members.0.name', 'Cliente VIP')
                ->has('unassignedClients.data', 0));
    }

    public function test_owner_email_appears_in_administrators_section(): void
    {
        config(['admin.owner_emails' => ['owner@example.com']]);

        $owner = User::factory()->create(['email' => 'owner@example.com']);
        User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('admins', 1)
                ->where('admins.0.email', 'owner@example.com')
                ->has('barbershops.data', 1)
                ->has('unassignedClients.data', 0));
    }
}
