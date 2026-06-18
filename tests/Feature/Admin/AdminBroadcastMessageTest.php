<?php

namespace Tests\Feature\Admin;

use App\Mail\AdminBroadcastMessageMail;
use App\Models\AdminBroadcastMessage;
use App\Models\AdminBroadcastMessageRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminBroadcastMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_message_to_barbershops_and_clients(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->post(route('admin.messages.store'), [
                'subject' => 'Aviso importante',
                'body' => 'Manutenção programada amanhã.',
                'send_email' => true,
                'audience' => 'all',
            ])
            ->assertRedirect(route('admin.messages.index'))
            ->assertSessionHas('status', 'admin-message-sent');

        $message = AdminBroadcastMessage::query()->first();

        $this->assertNotNull($message);
        $this->assertDatabaseHas('admin_broadcast_message_recipients', [
            'admin_broadcast_message_id' => $message->id,
            'recipient_user_id' => $barbershop->id,
        ]);
        $this->assertDatabaseHas('admin_broadcast_message_recipients', [
            'admin_broadcast_message_id' => $message->id,
            'recipient_user_id' => $client->id,
        ]);

        Mail::assertSent(AdminBroadcastMessageMail::class, 2);
    }

    public function test_recipient_sees_platform_popup_until_dismissed(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $client = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->post(route('admin.messages.store'), [
                'subject' => 'Promoção',
                'body' => 'Desconto especial hoje.',
                'send_email' => false,
                'audience' => 'clients',
            ]);

        $recipient = AdminBroadcastMessageRecipient::query()->firstOrFail();

        $this->actingAs($client)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('platformMessages', 1)
                ->where('platformMessages.0.subject', 'Promoção'));

        $this->actingAs($client)
            ->patch(route('platform-messages.dismiss', $recipient))
            ->assertRedirect();

        $this->assertNotNull($recipient->fresh()->dismissed_at);

        $this->actingAs($client)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->has('platformMessages', 0));
    }

    public function test_non_admin_cannot_access_admin_messages_page(): void
    {
        $barbershop = User::factory()->create();

        $this->actingAs($barbershop)
            ->get(route('admin.messages.index'))
            ->assertForbidden();
    }
}
