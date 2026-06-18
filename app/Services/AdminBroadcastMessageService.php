<?php

namespace App\Services;

use App\Mail\AdminBroadcastMessageMail;
use App\Models\AdminBroadcastMessage;
use App\Models\AdminBroadcastMessageRecipient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class AdminBroadcastMessageService
{
    public function tablesReady(): bool
    {
        return Schema::hasTable('admin_broadcast_messages')
            && Schema::hasTable('admin_broadcast_message_recipients');
    }

    /**
     * @param  list<int>  $recipientIds
     * @return Collection<int, User>
     */
    public function resolveRecipients(string $audience, array $recipientIds = []): Collection
    {
        $query = User::query()->regularUsers();

        return match ($audience) {
            'barbershops' => $query->barbershopAccounts()->orderBy('name')->get(),
            'clients' => $query->customers()->orderBy('name')->get(),
            'all' => $query->orderBy('name')->get(),
            'selected' => $this->resolveSelectedRecipients($recipientIds),
            default => throw new InvalidArgumentException('Público de destinatários inválido.'),
        };
    }

    /**
     * @param  list<int>  $recipientIds
     * @return Collection<int, User>
     */
    private function resolveSelectedRecipients(array $recipientIds): Collection
    {
        $recipientIds = collect($recipientIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            throw new InvalidArgumentException('Selecione ao menos um destinatário.');
        }

        $recipients = User::query()
            ->regularUsers()
            ->whereIn('id', $recipientIds)
            ->orderBy('name')
            ->get();

        if ($recipients->count() !== count($recipientIds)) {
            throw new InvalidArgumentException('Um ou mais destinatários selecionados são inválidos.');
        }

        return $recipients;
    }

    /**
     * @param  list<int>  $recipientIds
     */
    public function send(
        User $admin,
        string $subject,
        string $body,
        bool $sendEmail,
        string $audience,
        array $recipientIds = [],
    ): AdminBroadcastMessage {
        abort_unless($admin->isAdmin(), 403);
        abort_unless($this->tablesReady(), 503);

        $recipients = $this->resolveRecipients($audience, $recipientIds);

        if ($recipients->isEmpty()) {
            throw new InvalidArgumentException('Nenhum destinatário disponível para receber a mensagem.');
        }

        return DB::transaction(function () use ($admin, $subject, $body, $sendEmail, $audience, $recipients) {
            $message = AdminBroadcastMessage::query()->create([
                'admin_user_id' => $admin->id,
                'subject' => $subject,
                'body' => $body,
                'send_email' => $sendEmail,
                'audience' => $audience,
            ]);

            foreach ($recipients as $recipient) {
                $recipientRow = $message->recipients()->create([
                    'recipient_user_id' => $recipient->id,
                ]);

                if ($sendEmail) {
                    Mail::to($recipient->email)->send(
                        new AdminBroadcastMessageMail($message, $admin, $recipient),
                    );

                    $recipientRow->forceFill(['email_sent_at' => now()])->save();
                }
            }

            return $message->load(['recipients.recipient', 'admin']);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingPopupsFor(User $user): array
    {
        if (! $this->tablesReady() || $user->isAdmin()) {
            return [];
        }

        return AdminBroadcastMessageRecipient::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('dismissed_at')
            ->with(['message.admin:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (AdminBroadcastMessageRecipient $row) => [
                'recipient_id' => $row->id,
                'subject' => $row->message->subject,
                'body' => $row->message->body,
                'sender_name' => $row->message->admin->name,
                'created_at' => $row->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, AdminBroadcastMessage>
     */
    public function sentMessages(): Collection
    {
        if (! $this->tablesReady()) {
            return collect();
        }

        return AdminBroadcastMessage::query()
            ->withCount('recipients')
            ->latest()
            ->limit(50)
            ->get();
    }
}
