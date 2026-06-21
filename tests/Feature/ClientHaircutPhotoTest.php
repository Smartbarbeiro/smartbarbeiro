<?php

namespace Tests\Feature;

use App\Models\BarbershopMembership;
use App\Models\ClientHaircutPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientHaircutPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_view_haircut_photos_page(): void
    {
        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->get(route('haircuts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Haircuts/Index')
                ->has('photos', 0));
    }

    public function test_client_can_upload_haircut_photo(): void
    {
        Storage::fake('public');

        $barbershop = User::factory()->create();
        $customer = User::factory()->customer()->create();

        BarbershopMembership::create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->post(route('haircuts.store'), [
                'photo' => UploadedFile::fake()->create('corte.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('haircuts.index'))
            ->assertSessionHas('status', 'haircut-photo-uploaded');

        $photo = ClientHaircutPhoto::query()->first();

        $this->assertNotNull($photo);
        $this->assertSame($customer->id, $photo->user_id);
        $this->assertSame($barbershop->id, $photo->barbershop_user_id);
        Storage::disk('public')->assertExists($photo->photo_path);
    }

    public function test_client_can_delete_own_haircut_photo(): void
    {
        Storage::fake('public');

        $customer = User::factory()->customer()->create();
        $path = 'client-haircut-photos/'.$customer->id.'/corte.jpg';
        Storage::disk('public')->put($path, 'fake-image-content');

        $photo = ClientHaircutPhoto::create([
            'user_id' => $customer->id,
            'photo_path' => $path,
        ]);

        $this->actingAs($customer)
            ->delete(route('haircuts.destroy', $photo))
            ->assertRedirect(route('haircuts.index'))
            ->assertSessionHas('status', 'haircut-photo-deleted');

        $this->assertDatabaseMissing('client_haircut_photos', [
            'id' => $photo->id,
        ]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_barbershop_owner_cannot_access_haircut_photos_page(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->get(route('haircuts.index'))
            ->assertForbidden();
    }

    public function test_client_cannot_delete_another_users_haircut_photo(): void
    {
        Storage::fake('public');

        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $path = 'client-haircut-photos/'.$owner->id.'/corte.jpg';
        Storage::disk('public')->put($path, 'fake-image-content');

        $photo = ClientHaircutPhoto::create([
            'user_id' => $owner->id,
            'photo_path' => $path,
        ]);

        $this->actingAs($otherCustomer)
            ->delete(route('haircuts.destroy', $photo))
            ->assertForbidden();
    }
}
