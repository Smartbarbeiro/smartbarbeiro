<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'username' => $user->username,
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'username' => $user->username,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'profile_photo' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        $this->assertNotNull($user->profile_photo_url);
    }

    public function test_user_can_remove_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = 'profile-photos/'.$user->id.'/avatar.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        $user->update(['profile_photo_path' => $path]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'remove_profile_photo' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNull($user->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_public_profile_includes_profile_photo_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = 'profile-photos/'.$user->id.'/avatar.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        $user->update(['profile_photo_path' => $path]);

        $this->get(route('profile.public', $user->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('profile.profile_photo_url', $user->profile_photo_url));
    }

    public function test_barbershop_can_upload_background_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'background_photo' => UploadedFile::fake()->create('background.jpg', 500, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->background_photo_path);
        Storage::disk('public')->assertExists($user->background_photo_path);
        $this->assertNotNull($user->background_photo_url);
    }

    public function test_barbershop_can_remove_background_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = 'background-photos/'.$user->id.'/background.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        $user->update(['background_photo_path' => $path]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'remove_background_photo' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNull($user->background_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_public_profile_includes_background_photo_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = 'background-photos/'.$user->id.'/background.jpg';
        Storage::disk('public')->put($path, 'fake-image');
        $user->update(['background_photo_path' => $path]);

        $this->get(route('profile.public', $user->username))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('profile.background_photo_url', $user->background_photo_url));
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
