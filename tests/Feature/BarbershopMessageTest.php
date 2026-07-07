<?php

namespace Tests\Feature;

use App\Mail\BarbershopMessageMail;
use App\Models\BarbershopMembership;
use App\Models\BarbershopMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BarbershopMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Messages feature is currently hidden.');
    }

    public function test_barbershop_can_send_message_to_all_clients_with_email(): void
    {
        Mail::fake();

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        $this->actingAs($barbershop)
            ->post(route('messages.store'), [
                'subject' => 'Promoção da semana',
                'body' => 'Venha cortar o cabelo conosco!',
                'send_email' => true,
                'audience' => 'all',
            ])
            ->assertRedirect();

        $message = BarbershopMessage::query()->first();

        $this->assertNotNull($message);
        $this->assertDatabaseHas('barbershop_message_recipients', [
            'barbershop_message_id' => $message->id,
            'recipient_user_id' => $client->id,
        ]);

        Mail::assertSent(BarbershopMessageMail::class, function (BarbershopMessageMail $mail) use ($client) {
            return $mail->hasTo($client->email);
        });
    }

    public function test_barbershop_can_send_internal_message_without_email(): void
    {
        Mail::fake();

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        $this->actingAs($barbershop)
            ->post(route('messages.store'), [
                'subject' => 'Aviso interno',
                'body' => 'Mensagem apenas no app.',
                'send_email' => false,
                'audience' => 'all',
            ])
            ->assertRedirect();

        Mail::assertNothingSent();

        $this->assertDatabaseHas('barbershop_message_recipients', [
            'recipient_user_id' => $client->id,
            'email_sent_at' => null,
        ]);
    }

    public function test_client_can_view_inbox_and_mark_message_as_read(): void
    {
        Mail::fake();

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        $this->actingAs($barbershop)
            ->post(route('messages.store'), [
                'subject' => 'Horário especial',
                'body' => 'Abrimos no feriado.',
                'send_email' => false,
                'audience' => 'all',
            ]);

        $message = BarbershopMessage::query()->firstOrFail();

        $this->actingAs($client)
            ->get(route('messages.inbox'))
            ->assertOk();

        $this->actingAs($client)
            ->get(route('messages.show', $message))
            ->assertOk();

        $this->assertNotNull(
            $message->recipients()->where('recipient_user_id', $client->id)->value('read_at'),
        );
    }

    public function test_customer_cannot_access_compose_page(): void
    {
        $client = User::factory()->customer()->create();

        $this->actingAs($client)
            ->get(route('messages.compose'))
            ->assertForbidden();
    }

    public function test_client_cannot_view_another_clients_message(): void
    {
        Mail::fake();

        $barbershop = User::factory()->create();
        $client = User::factory()->customer()->create();
        $otherClient = User::factory()->customer()->create();

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $client->id,
        ]);

        BarbershopMembership::query()->create([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $otherClient->id,
        ]);

        $this->actingAs($barbershop)
            ->post(route('messages.store'), [
                'subject' => 'Somente para um cliente',
                'body' => 'Mensagem privada.',
                'send_email' => false,
                'audience' => 'selected',
                'recipient_ids' => [$client->id],
            ]);

        $message = BarbershopMessage::query()->firstOrFail();

        $this->actingAs($otherClient)
            ->get(route('messages.show', $message))
            ->assertForbidden();
    }
}
