<?php

namespace App\Services;

use App\Mail\BarbershopMessageMail;
use App\Models\BarbershopMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class BarbershopMessageService
{
    public function __construct(
        private BarbershopClientAudienceService $audienceService,
    ) {}

    public function messagesTablesReady(): bool
    {
        return Schema::hasTable('barbershop_messages')
            && Schema::hasTable('barbershop_message_recipients');
    }

    /**
     * @param  list<int>  $recipientIds
     */
    public function send(
        User $barbershop,
        string $subject,
        string $body,
        bool $sendEmail,
        string $audience = 'all',
        array $recipientIds = [],
    ): BarbershopMessage {
        abort_unless($barbershop->isBarbershop(), 403);
        abort_unless($this->messagesTablesReady(), 503);

        $recipients = $this->audienceService->resolveRecipients(
            $barbershop,
            $recipientIds,
            $audience,
        );

        if ($recipients->isEmpty()) {
            throw new InvalidArgumentException('Nenhum cliente disponível para receber a mensagem.');
        }

        return DB::transaction(function () use ($barbershop, $subject, $body, $sendEmail, $recipients) {
            $message = BarbershopMessage::query()->create([
                'barbershop_user_id' => $barbershop->id,
                'subject' => $subject,
                'body' => $body,
                'send_email' => $sendEmail,
            ]);

            foreach ($recipients as $recipient) {
                $recipientRow = $message->recipients()->create([
                    'recipient_user_id' => $recipient->id,
                ]);

                if ($sendEmail) {
                    Mail::to($recipient->email)->send(
                        new BarbershopMessageMail($message, $barbershop, $recipient),
                    );

                    $recipientRow->forceFill(['email_sent_at' => now()])->save();
                }
            }

            return $message->load(['recipients.recipient', 'barbershop']);
        });
    }

    public function unreadCountFor(User $user): int
    {
        if ($user->isBarbershop() || ! $this->messagesTablesReady()) {
            return 0;
        }

        return $user->receivedBarbershopMessages()
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, BarbershopMessage>
     */
    public function inboxFor(User $user): Collection
    {
        if (! $this->messagesTablesReady()) {
            return collect();
        }

        return BarbershopMessage::query()
            ->whereHas('recipients', fn ($query) => $query->where('recipient_user_id', $user->id))
            ->with([
                'barbershop:id,name,username',
                'recipients' => fn ($query) => $query->where('recipient_user_id', $user->id),
            ])
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, BarbershopMessage>
     */
    public function sentFor(User $barbershop): Collection
    {
        abort_unless($barbershop->isBarbershop(), 403);

        if (! $this->messagesTablesReady()) {
            return collect();
        }

        return $barbershop->sentBarbershopMessages()
            ->withCount('recipients')
            ->latest()
            ->get();
    }
}
