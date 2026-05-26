<?php

namespace Tests\Feature\Admin;

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

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 3));
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

    public function test_owner_email_from_config_is_treated_as_admin(): void
    {
        config(['admin.owner_emails' => ['owner@example.com']]);

        $owner = User::factory()->create(['email' => 'owner@example.com']);

        $this->assertTrue($owner->isAdmin());

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertOk();
    }
}
